<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class AdminProductoController extends Controller
{
    public function index(Request $request): View
    {
        $filtro = $request->query('categoria');
        if (! array_key_exists((string) $filtro, Producto::CATEGORIAS)) {
            $filtro = null;
        }

        $todos = Producto::orderBy('categoria')->orderBy('nombre')->get();

        return view('admin.productos.index', [
            'productos' => $filtro ? $todos->where('categoria', $filtro)->values() : $todos,
            'todos'     => $todos, // también alimenta el modal de actualización masiva
            'filtro'    => $filtro,
            'conteos'   => $todos->groupBy('categoria')->map->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Producto::create($this->validar($request));

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto creado correctamente.');
    }

        public function update(Request $request, Producto $producto): RedirectResponse
    {
        $datos = $this->validar($request, $producto);

        // Todo en una transacción y con la fila bloqueada: si el stock llega a cero, el aviso se
        // guarda junto con el cambio, y no se pisa un descuento hecho en ese mismo instante.
        DB::transaction(function () use ($producto, $datos) {
            Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail()->update($datos);
        });

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Dar de baja / de alta. No se borra: las comandas viejas conservan el producto.
     */
    public function cambiarEstado(Producto $producto): RedirectResponse
    {
        $nuevo = $producto->estado === 'activo' ? 'inactivo' : 'activo';
        $producto->update(['estado' => $nuevo]);

        $accion = $nuevo === 'activo' ? 'dado de alta' : 'dado de baja';

        return back()->with('success', "«{$producto->nombre}» fue {$accion}.");
    }

    private function validar(Request $request, ?Producto $producto = null): array
    {
        $datos = $request->validateWithBag('producto', [
            'nombre'    => ['required', 'string', 'max:100', Rule::unique('productos', 'nombre')->ignore($producto?->id)],
            'categoria' => ['required', Rule::in(array_keys(Producto::CATEGORIAS))],
            'tipo'      => ['required', Rule::in(array_keys(Producto::TIPOS))],
            'stock'     => ['nullable', 'required_if:tipo,unidad', 'integer', 'min:0', 'max:1000000'],
            'precio'    => ['required', 'integer', 'min:1', 'max:99999999'],
        ], [
            'nombre.required'   => 'Escribí el nombre del producto.',
            'nombre.unique'     => 'Ya existe un producto con ese nombre.',
            'categoria.in'      => 'La categoría elegida no es válida.',
            'tipo.in'           => 'El tipo de producto no es válido.',
            'stock.required_if' => 'Indicá el stock actual del producto.',
            'stock.integer'     => 'El stock debe ser un número entero.',
            'precio.required'   => 'Indicá el precio.',
            'precio.integer'    => 'El precio debe ser un número entero, en pesos.',
            'precio.min'        => 'El precio debe ser mayor a 0.',
        ]);

        // Los interruptores del formulario: tildado = true, ausente = false.
        $datos['estado'] = $request->boolean('activo') ? 'activo' : 'inactivo';
        $datos['permite_actualizacion_masiva'] = $request->boolean('permite_actualizacion_masiva');

        // Una preparación no lleva stock (el modelo también lo asegura).
        if ($datos['tipo'] === 'preparacion') {
            $datos['stock'] = null;
        }

        return $datos;
    }
}