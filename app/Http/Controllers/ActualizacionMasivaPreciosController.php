<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\ActualizacionMasivaPrecios;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ActualizacionMasivaPreciosController extends Controller
{
    /**
     * Calcula los precios nuevos sin guardar nada. La pantalla la usa para
     * la vista previa: lo que se ve es exactamente lo que después se aplica.
     */
    public function vistaPrevia(Request $request, ActualizacionMasivaPrecios $servicio): JsonResponse
    {
        $filas = $servicio->vistaPrevia($this->parametros($request, $servicio));

        return response()->json([
            'cantidad'      => $filas->count(),
            'hay_invalidos' => $filas->contains(fn ($fila) => ! $fila['valido']),
            'filas'         => $filas->map(fn ($fila) => [
                'id'            => $fila['producto']->id,
                'nombre'        => $fila['producto']->nombre,
                'precio_actual' => $fila['precio_actual'],
                'precio_nuevo'  => $fila['precio_nuevo'],
                'valido'        => $fila['valido'],
            ])->values(),
        ]);
    }

    public function aplicar(Request $request, ActualizacionMasivaPrecios $servicio): RedirectResponse
    {
        $cantidad = $servicio->aplicar($this->parametros($request, $servicio));

        $texto = $cantidad === 1 ? '1 producto modificado' : "{$cantidad} productos modificados";

        return redirect()
            ->route('admin.productos.index')
            ->with('success', "Precios actualizados: {$texto}.");
    }

    private function parametros(Request $request, ActualizacionMasivaPrecios $servicio): array
    {
        $datos = $request->validate([
            'alcance'     => ['required', Rule::in(ActualizacionMasivaPrecios::ALCANCES)],
            'categoria'   => ['nullable', 'required_if:alcance,categoria', Rule::in(array_keys(Producto::CATEGORIAS))],
            'ids'         => ['nullable', 'array'],
            'ids.*'       => ['integer'],
            'excluidos'   => ['nullable', 'array'],
            'excluidos.*' => ['integer'],
            'tipo'        => ['required', Rule::in([ActualizacionMasivaPrecios::TIPO_PORCENTAJE, ActualizacionMasivaPrecios::TIPO_MONTO])],
            'direccion'   => ['required', Rule::in([1, -1])],
            'valor'       => ['required', 'string', 'max:12'],
        ], [
            'alcance.in'           => 'El alcance elegido no es válido.',
            'categoria.required_if' => 'Elegí una categoría.',
            'valor.required'       => 'Ingresá un valor mayor a 0.',
        ]);

        $valor = $servicio->normalizarValor($datos['tipo'], $datos['valor']);

        if ($valor === null) {
            throw ValidationException::withMessages([
                'valor' => 'Ingresá un valor mayor a 0. Para porcentaje, hasta 2 decimales; para monto fijo, pesos enteros.',
            ]);
        }

        return [
            'alcance'   => $datos['alcance'],
            'categoria' => $datos['categoria'] ?? null,
            'ids'       => array_map('intval', $datos['ids'] ?? []),
            'excluidos' => array_map('intval', $datos['excluidos'] ?? []),
            'tipo'      => $datos['tipo'],
            'direccion' => (int) $datos['direccion'],
            'valor'     => $valor,
        ];
    }
}