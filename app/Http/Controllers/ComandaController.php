<?php

namespace App\Http\Controllers;

use App\Models\Comanda;
use App\Models\DetalleComanda;
use App\Models\Mesa;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComandaController extends Controller
{
    /**
     * PB-10: crear la comanda de una mesa (mesa, mozo, fecha/hora y estado).
     */
    public function store(Request $request, Mesa $mesa): RedirectResponse
    {
        $usuario = $request->user();

        $esAdministrador = $usuario->role === 'admin';
        $esMozoAsignado = $usuario->role === 'mozo' && (int) $mesa->mozo_id === (int) $usuario->id;

        abort_unless($esAdministrador || $esMozoAsignado, 403, 'Esta mesa no está asignada a tu usuario.');

        if (! $mesa->mozo_id) {
            return back()->withErrors(['comanda' => 'Primero hay que asignarle un mozo a la mesa.']);
        }

        $comandaAbierta = Comanda::abiertas()->where('mesa_id', $mesa->id)->first();

        if ($comandaAbierta) {
            return $this->volverAComanda($comandaAbierta)
                ->withErrors(['comanda' => 'La mesa ya tiene una comanda abierta.']);
        }

        $comanda = DB::transaction(function () use ($mesa) {
            $comanda = Comanda::create([
                'mesa_id' => $mesa->id,
                'mozo_id' => $mesa->mozo_id,
                'fecha' => now(),
                'estado' => 'abierta',
            ]);

            // La mesa pasa a "Mesa atendida" (verde). En la base el estado se llama "reservada".
            $mesa->update(['estado' => 'reservada']);

            return $comanda;
        });

        return $this->volverAComanda($comanda)->with('success', 'Comanda creada.');
    }

    /**
     * Ver una comanda.
     * - Abierta: se muestra en la ventana emergente de la pantalla de mesas (Módulo IV).
     * - Cerrada: se muestra en modo consulta, como parte del historial.
     */
    public function show(Request $request, Comanda $comanda): View|RedirectResponse
    {
        $this->verificarAcceso($request, $comanda);

        if ($comanda->estaAbierta()) {
            return $this->volverAComanda($comanda);
        }

        $comanda->load(['mesa', 'mozo', 'detalles.producto']);

        $productos = Producto::activos()
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get()
            ->groupBy('categoria');

        return view('comandas.show', compact('comanda', 'productos'));
    }

    /**
     * PB-11: agregar un producto con su cantidad.
     * Si el producto ya está en la comanda, se suma la cantidad.
     */
    public function agregarProducto(Request $request, Comanda $comanda): RedirectResponse
    {
        $this->verificarAcceso($request, $comanda);

        if ($respuesta = $this->rechazarSiEstaCerrada($comanda)) {
            return $respuesta;
        }

        $datos = $request->validate([
            'producto_id' => [
                'required',
                Rule::exists('productos', 'id')->where('estado', 'activo'),
            ],
            'cantidad' => ['required', 'integer', 'min:1', 'max:99'],
        ], [
            'producto_id.required' => 'Elegí un producto.',
            'producto_id.exists' => 'El producto no existe o no está disponible.',
            'cantidad.min' => 'La cantidad tiene que ser al menos 1.',
            'cantidad.max' => 'La cantidad máxima por carga es 99.',
        ]);

        DB::transaction(function () use ($comanda, $datos) {
            $producto = Producto::findOrFail($datos['producto_id']);

            $detalle = $comanda->detalles()->firstOrNew(['producto_id' => $producto->id]);

            if (! $detalle->exists) {
                // El precio se copia: un cambio de precio posterior no afecta esta comanda.
                $detalle->precio_unitario = $producto->precio;
                $detalle->cantidad = 0;
            }

            $detalle->cambiarCantidad($detalle->cantidad + (int) $datos['cantidad']);

            $comanda->recalcularTotales();
        });

        return $this->volverAComanda($comanda)->with('success', 'Producto agregado.');
    }

    /**
     * PB-12: cambiar la cantidad de un producto.
     */
    public function actualizarDetalle(Request $request, Comanda $comanda, DetalleComanda $detalle): RedirectResponse
    {
        $this->verificarAcceso($request, $comanda);

        if ($respuesta = $this->rechazarSiEstaCerrada($comanda)) {
            return $respuesta;
        }

        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1', 'max:99'],
        ], [
            'cantidad.min' => 'La cantidad tiene que ser al menos 1. Para sacar el producto, usá "Quitar".',
        ]);

        DB::transaction(function () use ($comanda, $detalle, $datos) {
            $detalle->cambiarCantidad((int) $datos['cantidad']);
            $comanda->recalcularTotales();
        });

        return $this->volverAComanda($comanda)->with('success', 'Cantidad actualizada.');
    }

    /**
     * PB-12: quitar un producto de la comanda.
     */
    public function quitarDetalle(Request $request, Comanda $comanda, DetalleComanda $detalle): RedirectResponse
    {
        $this->verificarAcceso($request, $comanda);

        if ($respuesta = $this->rechazarSiEstaCerrada($comanda)) {
            return $respuesta;
        }

        DB::transaction(function () use ($comanda, $detalle) {
            $detalle->delete();
            $comanda->recalcularTotales();
        });

        return $this->volverAComanda($comanda)->with('success', 'Producto quitado.');
    }

    /**
     * PB-14: cerrar la comanda. Calcula el total, la deja en el historial
     * y libera la mesa (vuelve a azul y queda sin mozo).
     */
    public function cerrar(Request $request, Comanda $comanda): RedirectResponse
    {
        $this->verificarAcceso($request, $comanda);

        if ($respuesta = $this->rechazarSiEstaCerrada($comanda)) {
            return $respuesta;
        }

        if (! $comanda->detalles()->exists()) {
            return $this->volverAComanda($comanda)
                ->withErrors(['comanda' => 'No se puede cerrar una comanda sin productos.']);
        }

        DB::transaction(function () use ($comanda) {
            $comanda->recalcularTotales();

            $comanda->update([
                'estado' => 'cerrada',
                'cerrada_en' => now(),
            ]);

            $comanda->mesa->update([
                'estado' => 'libre',
                'mozo_id' => null,
                'cantidad_personas' => 0,
            ]);
        });

        return redirect()
            ->route('mesas')
            ->with('success', 'Comanda de la mesa '.$comanda->mesa->numero.' cerrada. Total: $'.number_format((float) $comanda->total, 2, ',', '.'));
    }

    /**
     * Solo el administrador o el mozo de la comanda pueden verla o modificarla.
     */
    private function verificarAcceso(Request $request, Comanda $comanda): void
    {
        $usuario = $request->user();

        $esAdministrador = $usuario->role === 'admin';
        $esMozoDeLaComanda = $usuario->role === 'mozo' && (int) $comanda->mozo_id === (int) $usuario->id;

        abort_unless($esAdministrador || $esMozoDeLaComanda, 403, 'Esta comanda no es de tu mesa.');
    }

    /**
     * Vuelve a la pantalla de mesas con la ventana de esta comanda abierta.
     */
    private function volverAComanda(Comanda $comanda): RedirectResponse
    {
        return redirect()->route('mesas', ['comanda' => $comanda->id]);
    }

    private function rechazarSiEstaCerrada(Comanda $comanda): ?RedirectResponse
    {
        if ($comanda->estaAbierta()) {
            return null;
        }

        return back()->withErrors(['comanda' => 'La comanda está cerrada y ya no se puede modificar.']);
    }
}
