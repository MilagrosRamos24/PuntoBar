<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Services\ActualizacionMasivaPrecios;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ActualizacionMasivaPreciosTest extends TestCase
{
    use RefreshDatabase;

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

    private function datos(array $cambios = []): array
    {
        return $cambios + [
            'alcance'   => 'todos',
            'categoria' => null,
            'ids'       => [],
            'excluidos' => [],
            'tipo'      => 'porcentaje',
            'direccion' => 1,
            'valor'     => 1500,
        ];
    }

    private function servicio(): ActualizacionMasivaPrecios
    {
        return new ActualizacionMasivaPrecios();
    }

    public function test_porcentaje_sobre_una_categoria_respeta_los_bloqueados(): void
    {
        $cerveza = $this->producto(['precio' => 6200]);
        $fernet = $this->producto(['precio' => 5200]);
        $agua = $this->producto(['precio' => 2200, 'permite_actualizacion_masiva' => false]);
        $hamburguesa = $this->producto(['categoria' => 'comida', 'precio' => 9800]);

        $cantidad = $this->servicio()->aplicar($this->datos([
            'alcance'   => 'categoria',
            'categoria' => 'bebida',
        ]));

        $this->assertSame(2, $cantidad);
        $this->assertSame('7130.00', $cerveza->fresh()->precio);
        $this->assertSame('5980.00', $fernet->fresh()->precio);
        $this->assertSame('2200.00', $agua->fresh()->precio);
        $this->assertSame('9800.00', $hamburguesa->fresh()->precio);
    }

    public function test_monto_fijo_sobre_productos_seleccionados(): void
    {
        $a = $this->producto(['precio' => 10000]);
        $b = $this->producto(['precio' => 5000]);
        $c = $this->producto(['precio' => 3000]);

        $cantidad = $this->servicio()->aplicar($this->datos([
            'alcance' => 'seleccionados',
            'ids'     => [$a->id, $b->id],
            'tipo'    => 'monto',
            'valor'   => 500,
        ]));

        $this->assertSame(2, $cantidad);
        $this->assertSame('10500.00', $a->fresh()->precio);
        $this->assertSame('5500.00', $b->fresh()->precio);
        $this->assertSame('3000.00', $c->fresh()->precio);
    }

    public function test_todos_los_productos_menos_los_excluidos_y_los_bloqueados(): void
    {
        $a = $this->producto(['precio' => 1000]);
        $excluido = $this->producto(['precio' => 2000]);
        $bloqueado = $this->producto(['precio' => 3000, 'permite_actualizacion_masiva' => false]);

        $this->servicio()->aplicar($this->datos(['excluidos' => [$excluido->id]]));

        $this->assertSame('1150.00', $a->fresh()->precio);
        $this->assertSame('2000.00', $excluido->fresh()->precio);
        $this->assertSame('3000.00', $bloqueado->fresh()->precio);
    }

    public function test_el_bloqueo_tiene_prioridad_aunque_se_elija_a_mano(): void
    {
        $libre = $this->producto(['precio' => 1000]);
        $bloqueado = $this->producto(['precio' => 2000, 'permite_actualizacion_masiva' => false]);

        $this->servicio()->aplicar($this->datos([
            'alcance' => 'seleccionados',
            'ids'     => [$libre->id, $bloqueado->id],
        ]));

        $this->assertSame('1150.00', $libre->fresh()->precio);
        $this->assertSame('2000.00', $bloqueado->fresh()->precio);
    }

    public function test_si_solo_se_eligen_bloqueados_no_hay_nada_para_modificar(): void
    {
        $bloqueado = $this->producto(['precio' => 2000, 'permite_actualizacion_masiva' => false]);

        try {
            $this->servicio()->aplicar($this->datos([
                'alcance' => 'seleccionados',
                'ids'     => [$bloqueado->id],
            ]));
            $this->fail('Debería haber rechazado la actualización.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('productos', $e->errors());
        }

        $this->assertSame('2000.00', $bloqueado->fresh()->precio);
    }

    public function test_disminuir_por_porcentaje(): void
    {
        $producto = $this->producto(['precio' => 9800]);

        $this->servicio()->aplicar($this->datos(['direccion' => -1, 'valor' => 1000]));

        $this->assertSame('8820.00', $producto->fresh()->precio);
    }

    public function test_el_porcentaje_se_redondea_al_peso_mas_cercano(): void
    {
        $a = $this->producto(['precio' => 2200]);
        $b = $this->producto(['precio' => 100]);

        $this->servicio()->aplicar($this->datos([
            'alcance' => 'seleccionados',
            'ids'     => [$a->id],
            'valor'   => 700,
        ]));
        $this->servicio()->aplicar($this->datos([
            'alcance' => 'seleccionados',
            'ids'     => [$b->id],
            'valor'   => 50,
        ]));

        $this->assertSame('2354.00', $a->fresh()->precio);
        $this->assertSame('101.00', $b->fresh()->precio);
    }

    public function test_si_algun_precio_quedaria_en_cero_no_se_modifica_ninguno(): void
    {
        $a = $this->producto(['precio' => 1000]);
        $b = $this->producto(['precio' => 300]);

        try {
            $this->servicio()->aplicar($this->datos([
                'tipo'      => 'monto',
                'direccion' => -1,
                'valor'     => 500,
            ]));
            $this->fail('Debería haber rechazado el valor.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('valor', $e->errors());
        }

        $this->assertSame('1000.00', $a->fresh()->precio);
        $this->assertSame('300.00', $b->fresh()->precio);
    }

    public function test_solo_cambia_el_precio(): void
    {
        $producto = $this->producto([
            'nombre'    => 'Agua mineral',
            'categoria' => 'postre',
            'tipo'      => 'unidad',
            'stock'     => 10,
            'estado'    => 'inactivo',
            'precio'    => 1000,
        ]);

        $this->servicio()->aplicar($this->datos());

        $producto = $producto->fresh();
        $this->assertSame('1150.00', $producto->precio);
        $this->assertSame('Agua mineral', $producto->nombre);
        $this->assertSame('postre', $producto->categoria);
        $this->assertSame('unidad', $producto->tipo);
        $this->assertSame(10, $producto->stock);
        $this->assertSame('inactivo', $producto->estado);
        $this->assertTrue($producto->permite_actualizacion_masiva);
    }

    public function test_la_vista_previa_calcula_sin_guardar(): void
    {
        $producto = $this->producto(['precio' => 6200]);

        $filas = $this->servicio()->vistaPrevia($this->datos([
            'alcance' => 'seleccionados',
            'ids'     => [$producto->id],
        ]));

        $this->assertCount(1, $filas);
        $this->assertSame('6200.00', $filas->first()['precio_actual']);
        $this->assertSame('7130.00', $filas->first()['precio_nuevo']);
        $this->assertTrue($filas->first()['valido']);
        $this->assertSame('6200.00', $producto->fresh()->precio);
    }

    public function test_normalizar_el_valor_escrito(): void
    {
        $servicio = $this->servicio();

        $this->assertSame(1500, $servicio->normalizarValor('porcentaje', '15'));
        $this->assertSame(1250, $servicio->normalizarValor('porcentaje', '12,5'));
        $this->assertSame(1205, $servicio->normalizarValor('porcentaje', '12.05'));
        $this->assertSame(500, $servicio->normalizarValor('monto', '500'));
        $this->assertNull($servicio->normalizarValor('porcentaje', '0'));
        $this->assertNull($servicio->normalizarValor('porcentaje', ''));
        $this->assertNull($servicio->normalizarValor('porcentaje', 'abc'));
        $this->assertNull($servicio->normalizarValor('monto', '-5'));
        $this->assertNull($servicio->normalizarValor('monto', '12,5'));
    }
}