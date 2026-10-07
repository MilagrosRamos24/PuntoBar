<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Actualización masiva de precios.
 *
 * Parámetros ($datos):
 *  - alcance:   'todos' | 'categoria' | 'seleccionados'
 *  - categoria: 'bebida' | 'comida' | 'postre' (solo si alcance = 'categoria')
 *  - ids:       ids elegidos a mano (solo si alcance = 'seleccionados')
 *  - excluidos: ids desmarcados (alcance 'todos' o 'categoria')
 *  - tipo:      'porcentaje' | 'monto'
 *  - direccion: 1 (aumentar) | -1 (disminuir)
 *  - valor:     entero. Porcentaje: puntos básicos (15 % = 1500). Monto: pesos.
 *
 * Solo modifica el precio. El bloqueo (permite_actualizacion_masiva = false)
 * se aplica siempre en la consulta, sin importar el alcance elegido.
 * Toda la cuenta se hace con enteros (centavos): nada de floats con dinero.
 */
class ActualizacionMasivaPrecios
{
    public const ALCANCES = ['todos', 'categoria', 'seleccionados'];
    public const TIPO_PORCENTAJE = 'porcentaje';
    public const TIPO_MONTO = 'monto';

    /**
     * Convierte el texto que escribe el administrador en un entero:
     * porcentaje => puntos básicos ("15" => 1500, "12,5" => 1250),
     * monto => pesos enteros. Devuelve null si no es válido.
     */
    public function normalizarValor(string $tipo, string $texto): ?int
    {
        $texto = trim($texto);

        if ($tipo === self::TIPO_PORCENTAJE) {
            if (! preg_match('/^(\d{1,4})(?:[.,](\d{1,2}))?$/', $texto, $m)) {
                return null;
            }
            $valor = ((int) $m[1]) * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
        } else {
            if (! preg_match('/^\d{1,9}$/', $texto)) {
                return null;
            }
            $valor = (int) $texto;
        }

        return $valor > 0 ? $valor : null;
    }

    /**
     * Calcula los precios nuevos sin guardar nada.
     */
    public function vistaPrevia(array $datos): Collection
    {
        $this->validarAjuste($datos);

        return $this->consulta($datos)->get()->map(function (Producto $producto) use ($datos) {
            $nuevo = $this->calcularCentavos($producto, $datos);

            return [
                'producto'      => $producto,
                'precio_actual' => $this->aDecimal($this->aCentavos($producto->precio)),
                'precio_nuevo'  => $nuevo > 0 ? $this->aDecimal($nuevo) : null,
                'valido'        => $nuevo > 0,
            ];
        });
    }

    /**
     * Aplica la actualización. Devuelve la cantidad de productos modificados.
     * Todo o nada: si algo falla, no se modifica ningún precio.
     */
    public function aplicar(array $datos): int
    {
        $this->validarAjuste($datos);

        return DB::transaction(function () use ($datos) {
            $productos = $this->consulta($datos)->lockForUpdate()->get();

            if ($productos->isEmpty()) {
                throw ValidationException::withMessages([
                    'productos' => 'No hay productos para modificar con la selección actual.',
                ]);
            }

            $nuevos = [];
            foreach ($productos as $producto) {
                $nuevo = $this->calcularCentavos($producto, $datos);

                if ($nuevo <= 0) {
                    throw ValidationException::withMessages([
                        'valor' => "Con este valor, «{$producto->nombre}» quedaría con un precio de \$ 0 o menos.",
                    ]);
                }

                $nuevos[$producto->id] = $nuevo;
            }

            foreach ($productos as $producto) {
                $producto->precio = $this->aDecimal($nuevos[$producto->id]);
                $producto->save();
            }

            return $productos->count();
        });
    }

    private function consulta(array $datos): Builder
    {
        $alcance = $datos['alcance'] ?? null;

        if (! in_array($alcance, self::ALCANCES, true)) {
            throw ValidationException::withMessages(['alcance' => 'El alcance elegido no es válido.']);
        }

        // El bloqueo manda: se filtra siempre, antes que cualquier otro criterio.
        $query = Producto::query()->admitenActualizacionMasiva();

        if ($alcance === 'seleccionados') {
            $query->whereIn('id', $datos['ids'] ?? []);
        } else {
            if ($alcance === 'categoria') {
                $query->where('categoria', $datos['categoria'] ?? null);
            }
            $query->whereNotIn('id', $datos['excluidos'] ?? []);
        }

        return $query->orderBy('nombre');
    }

    private function validarAjuste(array $datos): void
    {
        if (! in_array($datos['tipo'] ?? null, [self::TIPO_PORCENTAJE, self::TIPO_MONTO], true)) {
            throw ValidationException::withMessages(['tipo' => 'El tipo de ajuste no es válido.']);
        }

        if (! is_int($datos['valor'] ?? null) || $datos['valor'] <= 0) {
            throw ValidationException::withMessages(['valor' => 'Ingresá un valor mayor a 0.']);
        }
    }

    /**
     * Precio nuevo en centavos. El porcentaje se redondea al peso más cercano.
     */
    private function calcularCentavos(Producto $producto, array $datos): int
    {
        $precio = $this->aCentavos($producto->precio);
        $direccion = ($datos['direccion'] ?? 1) < 0 ? -1 : 1;
        $valor = $datos['valor'];

        if ($datos['tipo'] === self::TIPO_PORCENTAJE) {
            // $valor está en puntos básicos (/10000) y el precio en centavos (/100).
            $numerador = $precio * (10000 + $direccion * $valor);

            return intdiv($numerador + 500000, 1000000) * 100;
        }

        return $precio + $direccion * $valor * 100;
    }

    private function aCentavos(string|int|float $precio): int
    {
        [$pesos, $centavos] = array_pad(explode('.', (string) $precio), 2, '0');

        return ((int) $pesos) * 100 + (int) str_pad(substr($centavos, 0, 2), 2, '0');
    }

    private function aDecimal(int $centavos): string
    {
        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }
}
