<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductosTest extends TestCase
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
            'nombre'                       => "Producto $n",
            'categoria'                    => 'bebida',
            'tipo'                         => 'preparacion',
            'precio'                       => 1000,
            'estado'                       => 'activo',
            'permite_actualizacion_masiva' => true,
        ]);
    }

    /** Datos tal como los manda el formulario del modal (los interruptores tildados mandan "1"). */
    private function datosProducto(array $cambios = []): array
    {
        return array_merge([
            'nombre'                       => 'Agua mineral 500 ml',
            'categoria'                    => 'bebida',
            'tipo'                         => 'unidad',
            'stock'                        => 10,
            'precio'                       => 1500,
            'activo'                       => '1',
            'permite_actualizacion_masiva' => '1',
        ], $cambios);
    }

    private function datosMasiva(array $cambios = []): array
    {
        return array_merge([
            'alcance'   => 'todos',
            'tipo'      => 'porcentaje',
            'direccion' => 1,
            'valor'     => '15',
        ], $cambios);
    }

    private function assertSinAcceso($respuesta): void
    {
        $this->assertContains($respuesta->getStatusCode(), [302, 401, 403]);
    }

    // ---------- Acceso ----------

    public function test_solo_el_administrador_accede_al_catalogo(): void
    {
        $this->assertSinAcceso($this->get('/admin/productos'));

        $this->actingAs($this->usuario('mozo'));
        $this->assertSinAcceso($this->get('/admin/productos'));
        $this->assertSinAcceso($this->post('/admin/productos', $this->datosProducto()));

        $this->assertDatabaseCount('productos', 0);
    }

    public function test_el_mozo_no_puede_actualizar_precios(): void
    {
        $producto = $this->producto(['precio' => 1000]);
        $this->actingAs($this->usuario('mozo'));

        $this->assertSinAcceso($this->post('/admin/productos/actualizacion-masiva', $this->datosMasiva()));
        $this->assertSinAcceso($this->postJson('/admin/productos/actualizacion-masiva/vista-previa', $this->datosMasiva()));

        $this->assertSame('1000.00', $producto->fresh()->precio);
    }

    // ---------- Listado ----------

    public function test_el_administrador_ve_el_catalogo_con_stock_y_candado(): void
    {
        $this->actingAs($this->usuario());
        $this->producto(['nombre' => 'Cerveza de prueba']);
        $this->producto(['nombre' => 'Gaseosa de prueba', 'tipo' => 'unidad', 'stock' => 0, 'permite_actualizacion_masiva' => false]);
        $this->producto(['nombre' => 'Agua de prueba', 'tipo' => 'unidad', 'stock' => 3]);

        $this->get('/admin/productos')
            ->assertOk()
            ->assertSee('Cerveza de prueba')
            ->assertSee('Sin stock')
            ->assertSee('Stock bajo')
            ->assertSee('Sin control')
            ->assertSee('No permite actualización masiva de precios');
    }

    public function test_el_listado_se_filtra_por_categoria(): void
    {
        $this->actingAs($this->usuario());
        $this->producto(['nombre' => 'Cerveza de prueba']);
        $this->producto(['nombre' => 'Milanesa de prueba', 'categoria' => 'comida']);

        $respuesta = $this->get('/admin/productos?categoria=comida')->assertOk();
        $this->assertSame(['Milanesa de prueba'], $respuesta->viewData('productos')->pluck('nombre')->all());

        // Una categoría inventada se ignora y muestra todo.
        $respuesta = $this->get('/admin/productos?categoria=inventada')->assertOk();
        $this->assertCount(2, $respuesta->viewData('productos'));
    }

    // ---------- Alta y edición ----------

    public function test_crear_un_producto_por_unidad_con_stock(): void
    {
        $this->actingAs($this->usuario());

        $this->post('/admin/productos', $this->datosProducto())
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/productos');

        $producto = Producto::where('nombre', 'Agua mineral 500 ml')->firstOrFail();
        $this->assertSame('unidad', $producto->tipo);
        $this->assertSame(10, $producto->stock);
        $this->assertSame('1500.00', $producto->precio);
        $this->assertSame('activo', $producto->estado);
        $this->assertTrue($producto->permite_actualizacion_masiva);
    }

    public function test_una_preparacion_no_guarda_stock(): void
    {
        $this->actingAs($this->usuario());

        $this->post('/admin/productos', $this->datosProducto([
            'nombre' => 'Fernet con cola',
            'tipo'   => 'preparacion',
            'stock'  => 99,
        ]))->assertSessionHasNoErrors();

        $this->assertNull(Producto::where('nombre', 'Fernet con cola')->firstOrFail()->stock);
    }

    public function test_un_producto_por_unidad_exige_stock(): void
    {
        $this->actingAs($this->usuario());

        $this->post('/admin/productos', $this->datosProducto(['stock' => '']))
            ->assertSessionHasErrorsIn('producto', ['stock']);

        $this->assertDatabaseCount('productos', 0);
    }

    public function test_rechaza_datos_invalidos(): void
    {
        $this->actingAs($this->usuario());
        $this->producto(['nombre' => 'Fernet']);

        $casos = [
            'nombre'    => ['nombre' => 'Fernet'],          // repetido
            'categoria' => ['categoria' => 'inventada'],
            'tipo'      => ['tipo' => 'inventado'],
            'precio'    => ['precio' => '0'],
        ];

        foreach ($casos as $campo => $cambio) {
            $this->post('/admin/productos', $this->datosProducto($cambio))
                ->assertSessionHasErrorsIn('producto', [$campo]);
        }

        foreach (['12.5', 'abc', '-5'] as $precio) {
            $this->post('/admin/productos', $this->datosProducto(['precio' => $precio]))
                ->assertSessionHasErrorsIn('producto', ['precio']);
        }

        $this->assertDatabaseCount('productos', 1);
    }

    public function test_editar_un_producto(): void
    {
        $this->actingAs($this->usuario());
        $producto = $this->producto(['nombre' => 'Gaseosa', 'tipo' => 'unidad', 'stock' => 10, 'precio' => 2000]);

        $this->put("/admin/productos/{$producto->id}", $this->datosProducto([
            'nombre' => 'Gaseosa 500 ml',
            'stock'  => 25,
            'precio' => 2600,
        ]))->assertSessionHasNoErrors()->assertRedirect('/admin/productos');

        $producto->refresh();
        $this->assertSame('Gaseosa 500 ml', $producto->nombre);
        $this->assertSame(25, $producto->stock);
        $this->assertSame('2600.00', $producto->precio);
    }

    public function test_editar_permite_conservar_el_mismo_nombre(): void
    {
        $this->actingAs($this->usuario());
        $producto = $this->producto(['nombre' => 'Gaseosa']);

        $this->put("/admin/productos/{$producto->id}", $this->datosProducto([
            'nombre' => 'Gaseosa',
            'tipo'   => 'preparacion',
        ]))->assertSessionHasNoErrors();
    }

    public function test_pasar_a_preparacion_borra_el_stock(): void
    {
        $this->actingAs($this->usuario());
        $producto = $this->producto(['tipo' => 'unidad', 'stock' => 5]);

        $this->put("/admin/productos/{$producto->id}", $this->datosProducto([
            'nombre' => $producto->nombre,
            'tipo'   => 'preparacion',
            'stock'  => 5,
        ]))->assertSessionHasNoErrors();

        $this->assertNull($producto->fresh()->stock);
    }

    public function test_los_interruptores_sin_tildar_dejan_inactivo_y_bloqueado(): void
    {
        $this->actingAs($this->usuario());
        $producto = $this->producto();

        $datos = $this->datosProducto(['nombre' => $producto->nombre, 'tipo' => 'preparacion']);
        unset($datos['activo'], $datos['permite_actualizacion_masiva']);

        $this->put("/admin/productos/{$producto->id}", $datos)->assertSessionHasNoErrors();

        $producto->refresh();
        $this->assertSame('inactivo', $producto->estado);
        $this->assertFalse($producto->permite_actualizacion_masiva);
    }

    // ---------- Baja y alta ----------

    public function test_dar_de_baja_y_de_alta_sin_borrar(): void
    {
        $this->actingAs($this->usuario());
        $producto = $this->producto(['estado' => 'activo']);

        $this->patch("/admin/productos/{$producto->id}/estado")->assertSessionHas('success');
        $this->assertSame('inactivo', $producto->fresh()->estado);

        $this->patch("/admin/productos/{$producto->id}/estado")->assertSessionHas('success');
        $this->assertSame('activo', $producto->fresh()->estado);
    }

    public function test_los_productos_no_se_pueden_borrar(): void
    {
        $this->actingAs($this->usuario());
        $producto = $this->producto();

        $this->delete("/admin/productos/{$producto->id}")->assertStatus(405);

        $this->assertDatabaseHas('productos', ['id' => $producto->id]);
    }

    // ---------- Actualización masiva ----------

    private function productosDeBebida(): array
    {
        return [
            'cerveza'     => $this->producto(['nombre' => 'Cerveza de prueba', 'precio' => 6200]),
            'fernet'      => $this->producto(['nombre' => 'Fernet de prueba', 'precio' => 5200]),
            'agua'        => $this->producto(['nombre' => 'Agua de prueba', 'precio' => 2200, 'permite_actualizacion_masiva' => false]),
            'hamburguesa' => $this->producto(['nombre' => 'Hamburguesa de prueba', 'categoria' => 'comida', 'precio' => 9800]),
        ];
    }

    public function test_la_vista_previa_calcula_sin_guardar(): void
    {
        $this->actingAs($this->usuario());
        $p = $this->productosDeBebida();

        $this->postJson('/admin/productos/actualizacion-masiva/vista-previa', $this->datosMasiva([
            'alcance'   => 'categoria',
            'categoria' => 'bebida',
        ]))
            ->assertOk()
            ->assertJsonPath('cantidad', 2)
            ->assertJsonPath('hay_invalidos', false)
            ->assertJsonPath('filas.0.nombre', 'Cerveza de prueba')
            ->assertJsonPath('filas.0.precio_actual', '6200.00')
            ->assertJsonPath('filas.0.precio_nuevo', '7130.00')
            ->assertJsonPath('filas.1.nombre', 'Fernet de prueba')
            ->assertJsonPath('filas.1.precio_nuevo', '5980.00');

        $this->assertSame('6200.00', $p['cerveza']->fresh()->precio);
    }

    public function test_aplicar_actualiza_la_categoria_y_respeta_los_bloqueados(): void
    {
        $this->actingAs($this->usuario());
        $p = $this->productosDeBebida();

        $this->post('/admin/productos/actualizacion-masiva', $this->datosMasiva([
            'alcance'   => 'categoria',
            'categoria' => 'bebida',
        ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/productos')
            ->assertSessionHas('success', 'Precios actualizados: 2 productos modificados.');

        $this->assertSame('7130.00', $p['cerveza']->fresh()->precio);
        $this->assertSame('5980.00', $p['fernet']->fresh()->precio);
        $this->assertSame('2200.00', $p['agua']->fresh()->precio);
        $this->assertSame('9800.00', $p['hamburguesa']->fresh()->precio);
    }

    public function test_aplicar_con_productos_seleccionados_y_monto_fijo(): void
    {
        $this->actingAs($this->usuario());
        $p = $this->productosDeBebida();

        $this->post('/admin/productos/actualizacion-masiva', $this->datosMasiva([
            'alcance' => 'seleccionados',
            'ids'     => [$p['cerveza']->id, $p['agua']->id],   // el agua está bloqueada
            'tipo'    => 'monto',
            'valor'   => '500',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('6700.00', $p['cerveza']->fresh()->precio);
        $this->assertSame('2200.00', $p['agua']->fresh()->precio);
        $this->assertSame('5200.00', $p['fernet']->fresh()->precio);
    }

    public function test_aplicar_con_exclusiones_y_disminuyendo(): void
    {
        $this->actingAs($this->usuario());
        $p = $this->productosDeBebida();

        $this->post('/admin/productos/actualizacion-masiva', $this->datosMasiva([
            'alcance'   => 'categoria',
            'categoria' => 'bebida',
            'excluidos' => [$p['fernet']->id],
            'direccion' => -1,
            'valor'     => '10',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('5580.00', $p['cerveza']->fresh()->precio);
        $this->assertSame('5200.00', $p['fernet']->fresh()->precio);
    }

    public function test_rechaza_valores_invalidos_sin_modificar_nada(): void
    {
        $this->actingAs($this->usuario());
        $p = $this->productosDeBebida();

        foreach (['abc', '0', '', '15,555'] as $valor) {
            $this->post('/admin/productos/actualizacion-masiva', $this->datosMasiva(['valor' => $valor]))
                ->assertSessionHasErrors('valor');
        }

        $this->postJson('/admin/productos/actualizacion-masiva/vista-previa', $this->datosMasiva(['valor' => '0']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('valor');

        $this->assertSame('6200.00', $p['cerveza']->fresh()->precio);
    }

    public function test_si_un_precio_quedaria_en_cero_no_se_modifica_ninguno(): void
    {
        $this->actingAs($this->usuario());
        $p = $this->productosDeBebida();

        $this->post('/admin/productos/actualizacion-masiva', $this->datosMasiva([
            'alcance'   => 'categoria',
            'categoria' => 'bebida',
            'tipo'      => 'monto',
            'direccion' => -1,
            'valor'     => '5500',   // la cerveza queda bien, el fernet queda en negativo
        ]))->assertSessionHasErrors('valor');

        $this->assertSame('6200.00', $p['cerveza']->fresh()->precio);
        $this->assertSame('5200.00', $p['fernet']->fresh()->precio);
    }
}