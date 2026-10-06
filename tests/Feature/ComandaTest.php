<?php

namespace Tests\Feature;

use App\Models\Comanda;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComandaTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $role, string $username): User
    {
        return User::factory()->create([
            'username' => $username,
            'role' => $role,
            'estado' => 'activo',
        ]);
    }

    private function mesaCon(?User $mozo, int $numero = 1): Mesa
    {
        return Mesa::create([
            'numero' => $numero,
            'estado' => 'libre',
            'mozo_id' => $mozo?->id,
            'cantidad_personas' => 2,
        ]);
    }

    private function producto(string $nombre, float $precio, string $estado = 'activo'): Producto
    {
        return Producto::create([
            'nombre' => $nombre,
            'categoria' => 'bebida',
            'precio' => $precio,
            'estado' => $estado,
        ]);
    }

    private function comandaAbierta(User $mozo, Mesa $mesa): Comanda
    {
        $this->actingAs($mozo)->post(route('comandas.store', $mesa));

        return Comanda::firstOrFail();
    }

    // ===== PB-10: crear comanda =====

    public function test_el_mozo_asignado_crea_la_comanda_con_mesa_mozo_fecha_y_estado(): void
    {
        $mozo = $this->usuario('mozo', 'mozo1');
        $mesa = $this->mesaCon($mozo);

        $respuesta = $this->actingAs($mozo)->post(route('comandas.store', $mesa));

        $comanda = Comanda::first();

        // Vuelve a la pantalla de mesas con la ventana de la comanda abierta.
        $respuesta->assertRedirect(route('mesas', ['comanda' => $comanda->id]));

        $this->assertSame($mesa->id, $comanda->mesa_id);
        $this->assertSame($mozo->id, $comanda->mozo_id);
        $this->assertSame('abierta', $comanda->estado);
        $this->assertNotNull($comanda->fecha);

        // La mesa pasa a "Mesa atendida".
        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'reservada']);
    }

    public function test_una_mesa_no_puede_tener_dos_comandas_abiertas(): void
    {
        $mozo = $this->usuario('mozo', 'mozo1');
        $mesa = $this->mesaCon($mozo);

        $this->actingAs($mozo)->post(route('comandas.store', $mesa));
        $this->actingAs($mozo)->post(route('comandas.store', $mesa))->assertSessionHasErrors('comanda');

        $this->assertSame(1, Comanda::count());
    }

    public function test_una_mesa_sin_mozo_no_puede_abrir_comanda(): void
    {
        $admin = $this->usuario('admin', 'admin');
        $mesa = $this->mesaCon(null);

        $this->actingAs($admin)->post(route('comandas.store', $mesa))->assertSessionHasErrors('comanda');

        $this->assertSame(0, Comanda::count());
    }

    public function test_un_mozo_no_asignado_no_puede_abrir_comanda(): void
    {
        $asignado = $this->usuario('mozo', 'mozo1');
        $otro = $this->usuario('mozo', 'mozo2');
        $mesa = $this->mesaCon($asignado);

        $this->actingAs($otro)->post(route('comandas.store', $mesa))->assertForbidden();
    }

    public function test_un_mozo_no_puede_ver_la_comanda_de_otro(): void
    {
        $asignado = $this->usuario('mozo', 'mozo1');
        $otro = $this->usuario('mozo', 'mozo2');
        $comanda = $this->comandaAbierta($asignado, $this->mesaCon($asignado));

        $this->actingAs($otro)->get(route('comandas.show', $comanda))->assertForbidden();
    }

    public function test_la_comanda_se_muestra_en_una_ventana_en_la_pantalla_de_mesas(): void
    {
        $mozo = $this->usuario('mozo', 'mozo1');
        $otro = $this->usuario('mozo', 'mozo2');
        $comanda = $this->comandaAbierta($mozo, $this->mesaCon($mozo));
        $gaseosa = $this->producto('Gaseosa', 2000);

        $this->post(route('comandas.productos.store', $comanda), ['producto_id' => $gaseosa->id, 'cantidad' => 2])
            ->assertRedirect(route('mesas', ['comanda' => $comanda->id]));

        // El mozo de la comanda ve la ventana con el producto y el total.
        $this->actingAs($mozo)
            ->get(route('mesas', ['comanda' => $comanda->id]))
            ->assertOk()
            ->assertSee('modal-comanda-'.$comanda->id)
            ->assertSee('2 × Gaseosa')
            ->assertSee('$4.000');

        // Otro mozo no recibe la ventana de una comanda ajena.
        $this->actingAs($otro)
            ->get(route('mesas'))
            ->assertOk()
            ->assertDontSee('modal-comanda-'.$comanda->id);
    }

    public function test_ver_una_comanda_abierta_lleva_a_su_ventana(): void
    {
        $mozo = $this->usuario('mozo', 'mozo1');
        $comanda = $this->comandaAbierta($mozo, $this->mesaCon($mozo));

        $this->get(route('comandas.show', $comanda))
            ->assertRedirect(route('mesas', ['comanda' => $comanda->id]));
    }

    // ===== PB-11: agregar productos y cantidades =====

    public function test_agregar_productos_con_cantidades_y_calcular_subtotal(): void
    {
        $mozo = $this->usuario('mozo', 'mozo1');
        $comanda = $this->comandaAbierta($mozo, $this->mesaCon($mozo));
        $gaseosa = $this->producto('Gaseosa', 2000);

        $this->post(route('comandas.productos.store', $comanda), ['producto_id' => $gaseosa->id, 'cantidad' => 2])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('detalle_comandas', [
            'comanda_id' => $comanda->id,
            'producto_id' => $gaseosa->id,
            'cantidad' => 2,
            'precio_unitario' => 2000,
            'total' => 4000,
        ]);
        $this->assertEquals(4000, (float) $comanda->fresh()->total);
    }

    public function test_agregar_un_producto_que_ya_esta_suma_la_cantidad(): void
    {
        $mozo = $this->usuario('mozo', 'mozo1');
        $comanda = $this->comandaAbierta($mozo, $this->mesaCon($mozo));
        $gaseosa = $this->producto('Gaseosa', 2000);

        $this->post(route('comandas.productos.store', $comanda), ['producto_id' => $gaseosa->id, 'cantidad' => 2]);
        $this->post(route('comandas.productos.store', $comanda), ['producto_id' => $gaseosa->id, 'cantidad' => 1]);

        $this->assertSame(1, $comanda->detalles()->count());
        $this->assertSame(3, $comanda->detalles()->first()->cantidad);
        $this->assertEquals(6000, (float) $comanda->fresh()->total);
    }

    public function test_no_se_pueden_agregar_productos_inactivos_ni_cantidades_invalidas(): void
    {
        $mozo = $this->usuario('mozo', 'mozo1');
        $comanda = $this->comandaAbierta($mozo, $this->mesaCon($mozo));
        $inactivo = $this->producto('Sin stock', 1000, 'inactivo');
        $activo = $this->producto('Agua', 1500);

        $this->post(route('comandas.productos.store', $comanda), ['producto_id' => $inactivo->id, 'cantidad' => 1])
            ->assertSessionHasErrors('producto_id');

        $this->post(route('comandas.productos.store', $comanda), ['producto_id' => $activo->id, 'cantidad' => 0])
            ->assertSessionHasErrors('cantidad');

        $this->assertSame(0, $comanda->detalles()->count());
    }

    public function test_un_cambio_de_precio_no_afecta_los_productos_ya_cargados(): void
    {
        $mozo = $this->usuario('mozo', 'mozo1');
        $comanda = $this->comandaAbierta($mozo, $this->mesaCon($mozo));
        $gaseosa = $this->producto('Gaseosa', 2000);

        $this->post(route('comandas.productos.store', $comanda), ['producto_id' => $gaseosa->id, 'cantidad' => 1]);
        $gaseosa->update(['precio' => 2500]);

        $this->assertEquals(2000, (float) $comanda->detalles()->first()->precio_unitario);
    }

    // ===== PB-12: modificar comanda =====

    public function test_cambiar_cantidad_y_quitar_producto_recalcula_el_total(): void
    {
        $mozo = $this->usuario('mozo', 'mozo1');
        $comanda = $this->comandaAbierta($mozo, $this->mesaCon($mozo));
        $gaseosa = $this->producto('Gaseosa', 2000);
        $papas = $this->producto('Papas fritas', 5000);

        $this->post(route('comandas.productos.store', $comanda), ['producto_id' => $gaseosa->id, 'cantidad' => 1]);
        $this->post(route('comandas.productos.store', $comanda), ['producto_id' => $papas->id, 'cantidad' => 1]);

        $detalleGaseosa = $comanda->detalles()->where('producto_id', $gaseosa->id)->first();
        $detallePapas = $comanda->detalles()->where('producto_id', $papas->id)->first();

        $this->patch(route('comandas.productos.update', [$comanda, $detalleGaseosa]), ['cantidad' => 3])
            ->assertSessionHasNoErrors();
        $this->assertEquals(11000, (float) $comanda->fresh()->total);

        $this->delete(route('comandas.productos.destroy', [$comanda, $detallePapas]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('detalle_comandas', ['id' => $detallePapas->id]);
        $this->assertEquals(6000, (float) $comanda->fresh()->total);
    }

    public function test_no_se_puede_modificar_un_detalle_de_otra_comanda(): void
    {
        $mozo = $this->usuario('mozo', 'mozo1');
        $comanda1 = $this->comandaAbierta($mozo, $this->mesaCon($mozo, 1));
        $this->post(route('comandas.store', $this->mesaCon($mozo, 2)));
        $comanda2 = Comanda::where('id', '!=', $comanda1->id)->firstOrFail();
        $gaseosa = $this->producto('Gaseosa', 2000);

        $this->post(route('comandas.productos.store', $comanda2), ['producto_id' => $gaseosa->id, 'cantidad' => 1]);
        $detalleAjeno = $comanda2->detalles()->first();

        $this->patch(route('comandas.productos.update', [$comanda1, $detalleAjeno]), ['cantidad' => 5])
            ->assertNotFound();
    }

    // ===== PB-13 y PB-14: estado y cierre =====

    public function test_cerrar_la_comanda_calcula_el_total_y_libera_la_mesa(): void
    {
        $mozo = $this->usuario('mozo', 'mozo1');
        $mesa = $this->mesaCon($mozo);
        $comanda = $this->comandaAbierta($mozo, $mesa);
        $gaseosa = $this->producto('Gaseosa', 2000);

        $this->post(route('comandas.productos.store', $comanda), ['producto_id' => $gaseosa->id, 'cantidad' => 2]);

        $this->post(route('comandas.cerrar', $comanda))->assertRedirect(route('mesas'));

        $comanda->refresh();
        $this->assertSame('cerrada', $comanda->estado);
        $this->assertNotNull($comanda->cerrada_en);
        $this->assertEquals(4000, (float) $comanda->total);

        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'estado' => 'libre', 'mozo_id' => null]);
    }

    public function test_no_se_puede_cerrar_una_comanda_sin_productos(): void
    {
        $mozo = $this->usuario('mozo', 'mozo1');
        $comanda = $this->comandaAbierta($mozo, $this->mesaCon($mozo));

        $this->post(route('comandas.cerrar', $comanda))->assertSessionHasErrors('comanda');

        $this->assertSame('abierta', $comanda->fresh()->estado);
    }

    public function test_una_comanda_cerrada_no_se_puede_modificar(): void
    {
        $mozo = $this->usuario('mozo', 'mozo1');
        $admin = $this->usuario('admin', 'admin');
        $comanda = $this->comandaAbierta($mozo, $this->mesaCon($mozo));
        $gaseosa = $this->producto('Gaseosa', 2000);

        $this->post(route('comandas.productos.store', $comanda), ['producto_id' => $gaseosa->id, 'cantidad' => 1]);
        $this->post(route('comandas.cerrar', $comanda));

        // El administrador puede verla en el historial, pero no modificarla.
        $this->actingAs($admin)->get(route('comandas.show', $comanda))->assertOk()->assertSee('Cerrada');

        $this->post(route('comandas.productos.store', $comanda), ['producto_id' => $gaseosa->id, 'cantidad' => 1])
            ->assertSessionHasErrors('comanda');

        $this->assertSame(1, $comanda->detalles()->first()->cantidad);
    }
}
