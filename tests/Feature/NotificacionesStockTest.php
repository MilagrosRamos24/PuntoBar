<?php

namespace Tests\Feature;

use App\Models\NotificacionStock;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificacionesStockTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $role = 'admin', string $estado = 'activo'): User
    {
        return User::factory()->create(['role' => $role, 'estado' => $estado]);
    }

    private function producto(array $datos = []): Producto
    {
        static $n = 0;
        $n++;

        return Producto::create($datos + [
            'nombre'                       => "Producto de prueba $n",
            'categoria'                    => 'bebida',
            'tipo'                         => 'unidad',
            'stock'                        => 3,
            'precio'                       => 1500,
            'estado'                       => 'activo',
            'permite_actualizacion_masiva' => true,
        ]);
    }

    /**
     * Descuenta y devuelve stock por el mismo camino que usan las comandas:
     * los métodos del modelo, que actualizan con una consulta directa.
     */
    private function descontar(Producto $producto, int $cantidad): bool
    {
        return Producto::findOrFail($producto->id)->descontarStock($cantidad);
    }

    private function devolver(Producto $producto, int $cantidad): void
    {
        Producto::findOrFail($producto->id)->devolverStock($cantidad);
    }

    /** Datos tal como los manda el formulario de edición del administrador. */
    private function formulario(Producto $producto, array $cambios = []): array
    {
        return array_merge([
            'nombre'                       => $producto->nombre,
            'categoria'                    => $producto->categoria,
            'tipo'                         => 'unidad',
            'stock'                        => $producto->stock,
            'precio'                       => 1500,
            'activo'                       => '1',
            'permite_actualizacion_masiva' => '1',
        ], $cambios);
    }

    private function assertSinAcceso($respuesta): void
    {
        $this->assertContains($respuesta->getStatusCode(), [302, 401, 403]);
    }

    // ---------- 1 a 3: detectar el cero y bloquear el producto ----------

    public function test_un_producto_con_stock_esta_disponible_y_no_genera_avisos(): void
    {
        $producto = $this->producto(['stock' => 3]);

        $this->assertTrue($this->descontar($producto, 1));

        $this->assertSame(2, $producto->fresh()->stock);
        $this->assertTrue($producto->fresh()->estaDisponible());
        $this->assertSame(0, NotificacionStock::count());
    }

    public function test_al_llegar_a_cero_desde_una_comanda_se_genera_el_aviso_y_el_producto_queda_bloqueado(): void
    {
        $producto = $this->producto(['nombre' => 'Gaseosa de prueba', 'stock' => 3]);

        $this->assertTrue($this->descontar($producto, 3));

        $producto->refresh();
        $this->assertSame(0, $producto->stock);
        $this->assertFalse($producto->estaDisponible());
        $this->assertFalse(Producto::disponibles()->whereKey($producto->id)->exists());

        $aviso = NotificacionStock::firstOrFail();
        $this->assertSame($producto->id, $aviso->producto_id);
        $this->assertSame('Gaseosa de prueba', $aviso->nombre_producto);
        $this->assertNotNull($aviso->sin_stock_at);
        $this->assertNull($aviso->leida_at);
        $this->assertNull($aviso->repuesto_at);
        $this->assertTrue($aviso->sigueSinStock());
    }

    public function test_la_edicion_manual_a_cero_tambien_genera_el_aviso(): void
    {
        $producto = $this->producto(['stock' => 5]);
        $this->actingAs($this->usuario());

        $this->put("/admin/productos/{$producto->id}", $this->formulario($producto, ['stock' => 0]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, NotificacionStock::count());
        $this->assertFalse($producto->fresh()->estaDisponible());
    }

    // ---------- 4: el contador del administrador ----------

    public function test_el_contador_de_la_campana_cuenta_las_no_leidas(): void
    {
        $this->actingAs($this->usuario());
        $uno = $this->producto(['stock' => 1]);
        $otro = $this->producto(['stock' => 1]);

        $this->get('/admin/productos')->assertOk()->assertDontSee('sin leer');

        $this->descontar($uno, 1);
        $this->get('/admin/productos')->assertSee('Notificaciones: 1 sin leer');

        $this->descontar($otro, 1);
        $this->get('/admin/productos')->assertSee('Notificaciones: 2 sin leer');
    }

    // ---------- 5: sin duplicados mientras siga en cero ----------

    public function test_mientras_siga_en_cero_no_se_duplican_los_avisos(): void
    {
        $producto = $this->producto(['stock' => 1]);
        $this->descontar($producto, 1);

        $producto->refresh();
        $producto->update(['precio' => 1800]);
        $producto->update(['nombre' => 'Nombre nuevo']);
        $producto->update(['stock' => 0]); // mismo valor: no es un cambio

        $this->actingAs($this->usuario())
            ->put("/admin/productos/{$producto->id}", $this->formulario($producto->fresh(), ['stock' => 0]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, NotificacionStock::count());
    }

    public function test_los_intentos_de_cargar_un_producto_sin_stock_no_duplican_el_aviso(): void
    {
        $producto = $this->producto(['stock' => 1]);
        $this->assertTrue($this->descontar($producto, 1));

        $this->assertFalse($this->descontar($producto, 1));
        $this->assertFalse($this->descontar($producto, 1));

        $this->assertSame(1, NotificacionStock::count());
    }

    public function test_la_base_impide_dos_avisos_abiertos_del_mismo_producto(): void
    {
        $producto = $this->producto();
        $datos = [
            'producto_id'      => $producto->id,
            'nombre_producto'  => $producto->nombre,
            'sin_stock_at'     => now(),
            'episodio_abierto' => 1,
        ];

        NotificacionStock::create($datos);

        $this->expectException(QueryException::class);
        NotificacionStock::create($datos);
    }

    // ---------- 6: marcar como leída ----------

    public function test_marcar_como_leida_actualiza_el_contador_y_conserva_el_historial(): void
    {
        $producto = $this->producto(['stock' => 1]);
        $this->descontar($producto, 1);
        $aviso = NotificacionStock::firstOrFail();
        $this->actingAs($this->usuario());

        $this->patch("/admin/notificaciones/{$aviso->id}/leida")->assertSessionHas('success');

        $aviso->refresh();
        $this->assertNotNull($aviso->leida_at);
        $this->assertSame(0, NotificacionStock::noLeidas()->count());
        $this->assertSame(1, NotificacionStock::count());
        $this->assertSame(0, $producto->fresh()->stock); // no toca el stock
        $this->get('/admin/productos')->assertDontSee('Notificaciones: 1 sin leer');
    }

    public function test_marcar_una_notificacion_inexistente_da_404(): void
    {
        $this->actingAs($this->usuario());

        $this->patch('/admin/notificaciones/99999/leida')->assertNotFound();
    }

    // ---------- 7 a 9: reposición y nuevo agotamiento ----------

    public function test_reponer_stock_desde_el_administrador_habilita_el_producto_y_conserva_el_aviso(): void
    {
        $producto = $this->producto(['stock' => 1]);
        $this->descontar($producto, 1);
        $aviso = NotificacionStock::firstOrFail();
        $momento = $aviso->sin_stock_at;
        $this->actingAs($this->usuario());

        $this->put("/admin/productos/{$producto->id}", $this->formulario($producto->fresh(), ['stock' => 20]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($producto->fresh()->estaDisponible());

        $aviso->refresh();
        $this->assertSame(1, NotificacionStock::count());          // sigue en el historial
        $this->assertFalse($aviso->sigueSinStock());
        $this->assertNotNull($aviso->repuesto_at);
        $this->assertTrue($aviso->sin_stock_at->equalTo($momento)); // conserva cuándo se agotó
        $this->assertNull($aviso->leida_at);                        // no se marca sola como leída
    }

    public function test_devolver_stock_desde_una_comanda_cierra_el_aviso_y_un_nuevo_agotamiento_genera_otro(): void
    {
        $producto = $this->producto(['stock' => 2]);
        $this->descontar($producto, 2);                  // se agota -> aviso 1

        $this->devolver($producto, 5);                   // quitan el producto de la comanda
        $this->assertTrue($producto->fresh()->estaDisponible());
        $this->assertSame(0, NotificacionStock::where('episodio_abierto', 1)->count());

        $this->assertTrue($this->descontar($producto, 5)); // vuelve a agotarse -> aviso 2

        $this->assertSame(2, NotificacionStock::count());
        $this->assertSame(1, NotificacionStock::where('episodio_abierto', 1)->count());
        $this->assertSame(1, NotificacionStock::whereNull('episodio_abierto')->count());
    }

    public function test_el_panel_conserva_el_historial_aunque_el_producto_recupere_stock(): void
    {
        $producto = $this->producto(['nombre' => 'Agua de prueba', 'stock' => 1]);
        $this->descontar($producto, 1);
        $this->devolver($producto, 10);
        $this->actingAs($this->usuario());

        $this->get('/admin/notificaciones')
            ->assertOk()
            ->assertSee('Agua de prueba')
            ->assertSee('se quedó sin stock')
            ->assertSee('repuesto el');
    }

    // ---------- 10: el mozo no accede ----------

    public function test_un_mozo_no_puede_consultar_ni_modificar_las_notificaciones(): void
    {
        $producto = $this->producto(['stock' => 1]);
        $this->descontar($producto, 1);
        $aviso = NotificacionStock::firstOrFail();

        $this->assertSinAcceso($this->get('/admin/notificaciones')); // sin sesión

        $this->actingAs($this->usuario('mozo'));
        $this->assertSinAcceso($this->get('/admin/notificaciones'));
        $this->assertSinAcceso($this->patch("/admin/notificaciones/{$aviso->id}/leida"));

        $this->assertNull($aviso->fresh()->leida_at);
    }

    public function test_la_campana_solo_se_muestra_al_administrador(): void
    {
        $this->actingAs($this->usuario('mozo'));
        $this->assertStringNotContainsString('pn-campana', (string) view('components.campana-notificaciones'));

        $this->actingAs($this->usuario('admin'));
        $this->assertStringContainsString('pn-campana', (string) view('components.campana-notificaciones'));
    }

    // ---------- 11: un producto inactivo no se reactiva ----------

    public function test_reponer_stock_no_reactiva_un_producto_inactivo(): void
    {
        $producto = $this->producto(['stock' => 5, 'estado' => 'inactivo']);
        $producto->update(['stock' => 0]);
        $this->actingAs($this->usuario());

        $datos = $this->formulario($producto->fresh(), ['stock' => 15]);
        unset($datos['activo']); // el formulario de un producto inactivo no manda "activo"

        $this->put("/admin/productos/{$producto->id}", $datos)->assertSessionHasNoErrors();

        $producto->refresh();
        $this->assertSame(15, $producto->stock);
        $this->assertSame('inactivo', $producto->estado);
        $this->assertFalse($producto->estaDisponible());
    }

    // ---------- 12 (parte del modelo): nunca se descuenta de más ----------

    public function test_el_stock_nunca_queda_negativo(): void
    {
        $producto = $this->producto(['stock' => 2]);

        $this->assertFalse($this->descontar($producto, 3));

        $this->assertSame(2, $producto->fresh()->stock);
        $this->assertSame(0, NotificacionStock::count());
    }

    public function test_una_preparacion_no_lleva_stock_ni_genera_avisos(): void
    {
        $preparacion = $this->producto(['tipo' => 'preparacion', 'stock' => null]);

        $this->assertTrue($this->descontar($preparacion, 4));
        $this->devolver($preparacion, 4);

        $this->assertNull($preparacion->fresh()->stock);
        $this->assertTrue($preparacion->fresh()->estaDisponible());
        $this->assertSame(0, NotificacionStock::count());
    }

    // ---------- Otros casos de borde ----------

    public function test_pasar_a_preparacion_cierra_el_aviso_abierto(): void
    {
        $producto = $this->producto(['stock' => 1]);
        $this->descontar($producto, 1);

        $producto->refresh();
        $producto->update(['tipo' => 'preparacion']);

        $aviso = NotificacionStock::firstOrFail();
        $this->assertFalse($aviso->sigueSinStock());
        $this->assertNotNull($aviso->repuesto_at);
        $this->assertTrue($producto->fresh()->estaDisponible());
    }

    public function test_crear_un_producto_con_stock_cero_no_genera_aviso(): void
    {
        $this->producto(['stock' => 0]);

        $this->assertSame(0, NotificacionStock::count());
    }

    public function test_el_panel_sin_notificaciones_muestra_un_mensaje_claro(): void
    {
        $this->actingAs($this->usuario());

        $this->get('/admin/notificaciones')
            ->assertOk()
            ->assertSee('No hay notificaciones pendientes');
    }
}