<?php

namespace Tests\Feature;

use App\Models\Comanda;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComandaFluidaTest extends TestCase
{
    use RefreshDatabase;

    private Comanda $comanda;
    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();
        $mozo = User::factory()->create(['role' => 'mozo', 'estado' => 'activo']);
        $mesa = Mesa::create([
            'numero' => 1, 'activa' => true, 'estado' => 'ocupada',
            'mozo_id' => $mozo->id, 'cantidad_personas' => 2, 'inicio_espera' => now(),
        ]);
        $this->comanda = Comanda::create([
            'mesa_id' => $mesa->id, 'mozo_id' => $mozo->id, 'fecha' => now(), 'estado' => 'abierta',
        ]);
        $this->producto = Producto::create([
            'nombre' => 'Gaseosa', 'categoria' => 'bebida', 'tipo' => 'unidad',
            'stock' => 10, 'precio' => 2000, 'estado' => 'activo',
        ]);
        $this->actingAs($mozo);
    }

    public function test_agrega_devuelve_html_total_stock_y_estado_sin_redireccion(): void
    {
        $respuesta = $this->postJson(route('comandas.productos.store', $this->comanda), [
            'producto_id' => $this->producto->id, 'cantidad' => 3,
        ])->assertOk()->assertJsonPath('mensaje', 'Producto agregado.')
            ->assertJsonPath('mesa.estado', 'reservada')->assertJsonPath('mesa.total', '$6.000');

        $this->assertStringContainsString('3 × Gaseosa', $respuesta->json('html'));
        $this->assertStringContainsString('quedan 7', $respuesta->json('html'));
        $this->assertSame(7, $this->producto->fresh()->stock);
        $this->assertNull($this->comanda->mesa->fresh()->inicio_espera);
    }

    public function test_actualiza_y_quita_devolviendo_stock_y_total(): void
    {
        $this->postJson(route('comandas.productos.store', $this->comanda), [
            'producto_id' => $this->producto->id, 'cantidad' => 3,
        ])->assertOk();
        $detalle = $this->comanda->detalles()->firstOrFail();

        $this->patchJson(route('comandas.productos.update', [$this->comanda, $detalle]), ['cantidad' => 2])
            ->assertOk()->assertJsonPath('mesa.total', '$4.000');
        $this->assertSame(8, $this->producto->fresh()->stock);

        $respuesta = $this->deleteJson(route('comandas.productos.destroy', [$this->comanda, $detalle]))
            ->assertOk()->assertJsonPath('mesa.total', '$0');
        $this->assertStringContainsString('No hay productos cargados', $respuesta->json('html'));
        $this->assertSame(10, $this->producto->fresh()->stock);
    }

    public function test_stock_insuficiente_devuelve_422_sin_modificar_stock(): void
    {
        $this->postJson(route('comandas.productos.store', $this->comanda), [
            'producto_id' => $this->producto->id, 'cantidad' => 11,
        ])->assertUnprocessable()->assertJsonValidationErrors('cantidad');
        $this->assertSame(10, $this->producto->fresh()->stock);
        $this->assertSame(0, $this->comanda->detalles()->count());
    }

    public function test_otro_mozo_no_puede_modificar_la_comanda(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'mozo', 'estado' => 'activo']))
            ->postJson(route('comandas.productos.store', $this->comanda), [
                'producto_id' => $this->producto->id, 'cantidad' => 1,
            ])->assertForbidden();
        $this->assertSame(10, $this->producto->fresh()->stock);
    }

    public function test_comanda_cerrada_devuelve_error_json(): void
    {
        $this->comanda->update(['estado' => 'cerrada']);
        $this->postJson(route('comandas.productos.store', $this->comanda), [
            'producto_id' => $this->producto->id, 'cantidad' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('comanda');
        $this->assertSame(10, $this->producto->fresh()->stock);
    }

    public function test_formulario_normal_conserva_la_redireccion(): void
    {
        $this->post(route('comandas.productos.store', $this->comanda), [
            'producto_id' => $this->producto->id, 'cantidad' => 1,
        ])->assertRedirect(route('mesas', ['comanda' => $this->comanda->id]));
    }
}
