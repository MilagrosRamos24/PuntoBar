<?php

namespace Tests\Feature;

use App\Models\Comanda;
use App\Models\ConfiguracionDescuento;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DescuentoComandaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $mozo;
    private Producto $picada;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['username' => 'admin', 'role' => 'admin', 'estado' => 'activo']);
        $this->mozo = User::factory()->create(['username' => 'mozo1', 'role' => 'mozo', 'estado' => 'activo']);

        // Preparación: no lleva stock, así el test se concentra en el descuento.
        $this->picada = Producto::create([
            'nombre' => 'Picada',
            'categoria' => 'comida',
            'tipo' => 'preparacion',
            'precio' => 10000,
            'estado' => 'activo',
        ]);
    }

    private function configurar(float $tope, float $porcentaje, bool $activo = true): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.descuento.update'), [
                'monto_tope' => $tope,
                'porcentaje' => $porcentaje,
                'activo' => $activo ? 1 : 0,
            ])
            ->assertSessionHasNoErrors();
    }

    private function comandaCon(int $cantidad, int $numeroMesa = 1): Comanda
    {
        $mesa = Mesa::create([
            'numero' => $numeroMesa,
            'estado' => 'libre',
            'mozo_id' => $this->mozo->id,
            'cantidad_personas' => 2,
        ]);

        $this->actingAs($this->mozo)->post(route('comandas.store', $mesa));
        $comanda = Comanda::where('mesa_id', $mesa->id)->firstOrFail();

        $this->post(route('comandas.productos.store', $comanda), [
            'producto_id' => $this->picada->id,
            'cantidad' => $cantidad,
        ])->assertSessionHasNoErrors();

        return $comanda->fresh();
    }

    // ===== Pantalla de configuración =====

    public function test_el_administrador_ve_la_configuracion(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.descuento.edit'))
            ->assertOk()
            ->assertSee('Monto tope')
            ->assertSee('Hoy no se aplica ningún descuento.');
    }

    public function test_un_mozo_no_puede_configurar_el_descuento(): void
    {
        $this->actingAs($this->mozo)->get(route('admin.descuento.edit'))->assertForbidden();

        $this->actingAs($this->mozo)
            ->put(route('admin.descuento.update'), ['monto_tope' => 1000, 'porcentaje' => 50, 'activo' => 1])
            ->assertForbidden();
    }

    public function test_guardar_la_configuracion(): void
    {
        $this->configurar(20000, 10);

        $configuracion = ConfiguracionDescuento::actual();
        $this->assertEquals(20000, (float) $configuracion->monto_tope);
        $this->assertEquals(10, (float) $configuracion->porcentaje);
        $this->assertTrue($configuracion->activo);
    }

    public function test_rechaza_valores_invalidos(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.descuento.update'), ['monto_tope' => -5, 'porcentaje' => 150, 'activo' => 1])
            ->assertSessionHasErrors(['monto_tope', 'porcentaje']);
    }

    // ===== Aplicación en las comandas =====

    public function test_se_aplica_cuando_el_subtotal_supera_el_tope(): void
    {
        $this->configurar(20000, 10);

        $comanda = $this->comandaCon(3); // subtotal 30.000

        $this->assertEquals(30000, (float) $comanda->subtotal);
        $this->assertEquals(3000, (float) $comanda->descuento);
        $this->assertEquals(27000, (float) $comanda->total);
    }

    public function test_no_se_aplica_si_el_subtotal_iguala_o_no_llega_al_tope(): void
    {
        $this->configurar(20000, 10);

        $comanda = $this->comandaCon(2); // subtotal 20.000: no lo supera

        $this->assertEquals(0, (float) $comanda->descuento);
        $this->assertEquals(20000, (float) $comanda->total);
    }

    public function test_se_quita_si_el_subtotal_vuelve_a_quedar_por_debajo(): void
    {
        $this->configurar(20000, 10);
        $comanda = $this->comandaCon(3);
        $detalle = $comanda->detalles()->first();

        $this->actingAs($this->mozo)
            ->patch(route('comandas.productos.update', [$comanda, $detalle]), ['cantidad' => 1])
            ->assertSessionHasNoErrors();

        $comanda->refresh();
        $this->assertEquals(0, (float) $comanda->descuento);
        $this->assertEquals(10000, (float) $comanda->total);
    }

    public function test_desactivado_no_se_aplica(): void
    {
        $this->configurar(20000, 10, activo: false);

        $comanda = $this->comandaCon(3);

        $this->assertEquals(0, (float) $comanda->descuento);
        $this->assertEquals(30000, (float) $comanda->total);
    }

    public function test_cambiar_la_configuracion_recalcula_las_abiertas_pero_no_las_cerradas(): void
    {
        $this->configurar(20000, 10);

        $cerrada = $this->comandaCon(3, 1);
        $this->actingAs($this->mozo)->post(route('comandas.cerrar', $cerrada));

        $abierta = $this->comandaCon(3, 2);

        $this->configurar(20000, 20);

        // La abierta toma el nuevo porcentaje.
        $this->assertEquals(6000, (float) $abierta->fresh()->descuento);
        $this->assertEquals(24000, (float) $abierta->fresh()->total);

        // La cerrada conserva el descuento con el que se cerró.
        $this->assertEquals(3000, (float) $cerrada->fresh()->descuento);
        $this->assertEquals(27000, (float) $cerrada->fresh()->total);
    }

    public function test_el_descuento_se_ve_en_la_ventana_de_la_comanda(): void
    {
        $this->configurar(20000, 10);
        $comanda = $this->comandaCon(3);

        $this->actingAs($this->mozo)
            ->get(route('mesas', ['comanda' => $comanda->id]))
            ->assertOk()
            ->assertSee('Descuento')
            ->assertSee('$3.000')
            ->assertSee('$27.000');
    }
}
