<?php

namespace Tests\Feature;

use App\Models\Comanda;
use App\Models\DetalleComanda;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiberarMesaAdminTest extends TestCase
{
    use RefreshDatabase;

    private Mesa $mesa;
    private Comanda $comanda;
    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create(['role' => 'admin', 'estado' => 'activo']);
        $mozo = User::factory()->create(['role' => 'mozo', 'estado' => 'activo']);
        $this->mesa = Mesa::create([
            'numero' => 1, 'activa' => true, 'estado' => 'reservada',
            'mozo_id' => $mozo->id, 'cantidad_personas' => 2,
        ]);
        $this->comanda = Comanda::create([
            'mesa_id' => $this->mesa->id, 'mozo_id' => $mozo->id,
            'fecha' => now(), 'estado' => 'abierta',
        ]);
        $this->producto = Producto::create([
            'nombre' => 'Gaseosa', 'categoria' => 'bebida', 'tipo' => 'unidad',
            'stock' => 9, 'precio' => 2000, 'estado' => 'activo',
        ]);
        DetalleComanda::create([
            'comanda_id' => $this->comanda->id, 'producto_id' => $this->producto->id,
            'cantidad' => 1, 'precio_unitario' => 2000, 'total' => 2000,
        ]);
        $this->comanda->recalcularTotales();
        $this->actingAs($admin);
    }

    public function test_no_libera_la_mesa_con_comanda_abierta_y_devuelve_accion_de_cierre(): void
    {
        $this->putJson(route('mesas.estado', $this->mesa), ['estado' => 'libre'])
            ->assertConflict()->assertJsonPath('codigo', 'cerrar_comanda')
            ->assertJsonPath('comanda_id', $this->comanda->id);
        $this->assertSame('reservada', $this->mesa->fresh()->estado);
        $this->assertSame('abierta', $this->comanda->fresh()->estado);
        $this->assertNotNull($this->mesa->fresh()->mozo_id);
    }

    public function test_cerrar_conserva_importe_historico_y_libera_la_mesa_sin_devolver_stock(): void
    {
        $importe = $this->comanda->fresh()->total;
        $respuesta = $this->postJson(route('comandas.cerrar', $this->comanda))
            ->assertOk()->assertJsonPath('comanda_cerrada_id', $this->comanda->id);
        $mesa = $this->mesa->fresh();
        $this->assertSame('cerrada', $this->comanda->fresh()->estado);
        $this->assertSame($importe, $this->comanda->fresh()->total);
        $this->assertNotNull($this->comanda->fresh()->cerrada_en);
        $this->assertSame('libre', $mesa->estado);
        $this->assertNull($mesa->mozo_id);
        $this->assertSame(0, $mesa->cantidad_personas);
        $this->assertNull($mesa->ultimo_pedido_en);
        $this->assertNull($mesa->inicio_espera);
        $this->assertNull($mesa->comandaAbierta);
        $this->assertSame(9, $this->producto->fresh()->stock);
        $this->assertStringContainsString('data-mesa-mozo="Sin asignar"', $respuesta->json('html'));
    }

    public function test_formulario_normal_tampoco_puede_saltar_el_cierre(): void
    {
        $this->from(route('mesas'))->put(route('mesas.estado', $this->mesa), ['estado' => 'libre'])
            ->assertSessionHasErrors('estado');
        $this->assertSame('reservada', $this->mesa->fresh()->estado);
    }

    public function test_cerrar_dos_veces_no_modifica_el_importe_ni_el_stock(): void
    {
        $this->postJson(route('comandas.cerrar', $this->comanda))->assertOk();
        $importe = $this->comanda->fresh()->total;
        $this->postJson(route('comandas.cerrar', $this->comanda))->assertUnprocessable();
        $this->assertSame($importe, $this->comanda->fresh()->total);
        $this->assertSame(9, $this->producto->fresh()->stock);
    }

    public function test_otro_mozo_no_puede_cerrar_la_comanda(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'mozo', 'estado' => 'activo']));
        $this->postJson(route('comandas.cerrar', $this->comanda))->assertForbidden();
        $this->assertSame('abierta', $this->comanda->fresh()->estado);
    }

    public function test_mesa_sin_comanda_abierta_se_libera_y_limpia_la_atencion(): void
    {
        $this->comanda->update(['estado' => 'cerrada', 'cerrada_en' => now()]);
        $this->putJson(route('mesas.estado', $this->mesa), ['estado' => 'libre'])->assertOk();
        $mesa = $this->mesa->fresh();
        $this->assertSame('libre', $mesa->estado);
        $this->assertNull($mesa->mozo_id);
        $this->assertSame(0, $mesa->cantidad_personas);
    }

    public function test_comanda_vacia_no_libera_la_mesa_si_el_cierre_es_rechazado(): void
    {
        $this->comanda->detalles()->delete();
        $this->postJson(route('comandas.cerrar', $this->comanda))
            ->assertUnprocessable()->assertJsonValidationErrors('comanda');
        $this->assertSame('reservada', $this->mesa->fresh()->estado);
        $this->assertSame('abierta', $this->comanda->fresh()->estado);
    }

}
