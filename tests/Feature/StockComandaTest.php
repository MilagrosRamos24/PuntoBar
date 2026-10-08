<?php

namespace Tests\Feature;

use App\Models\Comanda;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockComandaTest extends TestCase
{
    use RefreshDatabase;

    private User $mozo;
    private Comanda $comanda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mozo = User::factory()->create([
            'username' => 'mozo1',
            'role' => 'mozo',
            'estado' => 'activo',
        ]);

        $mesa = Mesa::create([
            'numero' => 1,
            'estado' => 'libre',
            'mozo_id' => $this->mozo->id,
            'cantidad_personas' => 2,
        ]);

        $this->actingAs($this->mozo)->post(route('comandas.store', $mesa));
        $this->comanda = Comanda::firstOrFail();
    }

    private function porUnidad(string $nombre, int $stock): Producto
    {
        return Producto::create([
            'nombre' => $nombre,
            'categoria' => 'bebida',
            'tipo' => 'unidad',
            'stock' => $stock,
            'precio' => 2000,
            'estado' => 'activo',
        ]);
    }

    private function agregar(Producto $producto, int $cantidad)
    {
        return $this->post(route('comandas.productos.store', $this->comanda), [
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
        ]);
    }

    public function test_agregar_un_producto_descuenta_el_stock(): void
    {
        $gaseosa = $this->porUnidad('Gaseosa', 10);

        $this->agregar($gaseosa, 3)->assertSessionHasNoErrors();

        $this->assertSame(7, $gaseosa->fresh()->stock);
    }

    public function test_no_se_puede_agregar_mas_de_lo_que_hay(): void
    {
        $gaseosa = $this->porUnidad('Gaseosa', 2);

        $this->agregar($gaseosa, 3)->assertSessionHasErrors('cantidad');

        $this->assertSame(2, $gaseosa->fresh()->stock);
        $this->assertSame(0, $this->comanda->detalles()->count());
    }

    public function test_una_preparacion_no_descuenta_stock(): void
    {
        $papas = Producto::create([
            'nombre' => 'Papas fritas',
            'categoria' => 'comida',
            'tipo' => 'preparacion',
            'precio' => 5000,
            'estado' => 'activo',
        ]);

        $this->agregar($papas, 5)->assertSessionHasNoErrors();

        $this->assertNull($papas->fresh()->stock);
        $this->assertSame(5, $this->comanda->detalles()->first()->cantidad);
    }

    public function test_cambiar_la_cantidad_descuenta_o_devuelve_la_diferencia(): void
    {
        $gaseosa = $this->porUnidad('Gaseosa', 10);
        $this->agregar($gaseosa, 2);
        $detalle = $this->comanda->detalles()->first();

        $this->patch(route('comandas.productos.update', [$this->comanda, $detalle]), ['cantidad' => 5])
            ->assertSessionHasNoErrors();
        $this->assertSame(5, $gaseosa->fresh()->stock);

        $this->patch(route('comandas.productos.update', [$this->comanda, $detalle]), ['cantidad' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame(9, $gaseosa->fresh()->stock);
    }

    public function test_no_se_puede_subir_la_cantidad_por_encima_del_stock(): void
    {
        $gaseosa = $this->porUnidad('Gaseosa', 3);
        $this->agregar($gaseosa, 2);
        $detalle = $this->comanda->detalles()->first();

        $this->patch(route('comandas.productos.update', [$this->comanda, $detalle]), ['cantidad' => 6])
            ->assertSessionHasErrors('cantidad');

        $this->assertSame(1, $gaseosa->fresh()->stock);
        $this->assertSame(2, $detalle->fresh()->cantidad);
    }

    public function test_quitar_un_producto_devuelve_el_stock(): void
    {
        $gaseosa = $this->porUnidad('Gaseosa', 10);
        $this->agregar($gaseosa, 4);
        $detalle = $this->comanda->detalles()->first();

        $this->delete(route('comandas.productos.destroy', [$this->comanda, $detalle]))
            ->assertSessionHasNoErrors();

        $this->assertSame(10, $gaseosa->fresh()->stock);
    }

    public function test_los_productos_sin_stock_no_se_ofrecen_en_la_comanda(): void
    {
        $this->porUnidad('Gaseosa agotada', 0);
        $this->porUnidad('Agua con stock', 5);

        $this->get(route('mesas', ['comanda' => $this->comanda->id]))
            ->assertOk()
            ->assertSee('Agua con stock')
            ->assertDontSee('Gaseosa agotada');
    }
}
