<?php

namespace App\Http\Controllers;

use App\Models\Comanda;
use App\Models\DetalleComanda;
use App\Models\Mesa;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        if (
            $mesa->estado === 'libre' ||
            (int) $mesa->cantidad_personas < 1
            ) {
                return back()->withErrors([
                    'comanda' =>
                    'Primero registrá la atención indicando la cantidad de personas.',
                    ]);
                    }

        $comanda = DB::transaction(function () use ($mesa) {
            $comanda = Comanda::create([
                'mesa_id' => $mesa->id,
                'mozo_id' => $mesa->mozo_id,
                'fecha' => now(),
                'estado' => 'abierta',
            ]);
            // Una comanda vacía todavía está esperando el primer pedido.
            // Conservamos la fecha si la espera ya había comenzado.
            // Abrir una comanda vacía deja la mesa esperando.
            // Si ya tenía una alerta, la conservamos.
            $mesa->update([
                'estado' => $mesa->estado === 'pendiente_de_cierre'
                ? 'pendiente_de_cierre'
                : 'ocupada',
                'inicio_espera' => $mesa->inicio_espera ?? now(),
                ]);

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
     * Si el producto lleva stock, se verifica que alcance y se descuenta.
     */
    public function agregarProducto(Request $request, Comanda $comanda): RedirectResponse|JsonResponse
    {
        $this->verificarAcceso($request, $comanda);

        if ($request->expectsJson() && ! $comanda->estaAbierta()) {
            throw ValidationException::withMessages([
                'comanda' => 'La comanda está cerrada y ya no se puede modificar.',
            ]);
        }

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
            $cantidad = (int) $datos['cantidad'];

            $this->descontarOFallar($producto, $cantidad);

            $detalle = $comanda->detalles()->firstOrNew(['producto_id' => $producto->id]);

            if (! $detalle->exists) {
                // El precio se copia: un cambio de precio posterior no afecta esta comanda.
                $detalle->precio_unitario = $producto->precio;
                $detalle->cantidad = 0;
            }

            $detalle->cambiarCantidad($detalle->cantidad + $cantidad);

            $comanda->recalcularTotales();
            // El pedido ya tiene un producto: termina la espera
            // y la mesa pasa a "Mesa atendida".
            $comanda->mesa()->update([
                'ultimo_pedido_en' => now(),
                'estado' => 'reservada',
                'inicio_espera' => null,
                ]);
        });

        return $this->responderProducto($request, $comanda, 'Producto agregado.');
    }

    /**
     * PB-12: cambiar la cantidad de un producto.
     * Si aumenta, se descuenta la diferencia del stock; si baja, se devuelve.
     */
    public function actualizarDetalle(Request $request, Comanda $comanda, DetalleComanda $detalle): RedirectResponse|JsonResponse
    {
        $this->verificarAcceso($request, $comanda);

        if ($request->expectsJson() && ! $comanda->estaAbierta()) {
            throw ValidationException::withMessages([
                'comanda' => 'La comanda está cerrada y ya no se puede modificar.',
            ]);
        }

        if ($respuesta = $this->rechazarSiEstaCerrada($comanda)) {
            return $respuesta;
        }

        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1', 'max:99'],
        ], [
            'cantidad.min' => 'La cantidad tiene que ser al menos 1. Para sacar el producto, usá "Quitar".',
        ]);

        DB::transaction(function () use ($comanda, $detalle, $datos) {
            $nuevaCantidad = (int) $datos['cantidad'];
            $diferencia = $nuevaCantidad - $detalle->cantidad;

            if ($diferencia > 0) {
                $this->descontarOFallar($detalle->producto, $diferencia);
            } elseif ($diferencia < 0) {
                $detalle->producto->devolverStock(-$diferencia);
            }

            $detalle->cambiarCantidad($nuevaCantidad);
            $comanda->recalcularTotales();

            if ($diferencia > 0) {
                $comanda->mesa()->update(['ultimo_pedido_en' => now()]);
            }
        });

        return $this->responderProducto($request, $comanda, 'Cantidad actualizada.');
    }

    /**
     * PB-12: quitar un producto de la comanda. Si lleva stock, se devuelve.
     */
    public function quitarDetalle(Request $request, Comanda $comanda, DetalleComanda $detalle): RedirectResponse|JsonResponse
    {
        $this->verificarAcceso($request, $comanda);

        if ($request->expectsJson() && ! $comanda->estaAbierta()) {
            throw ValidationException::withMessages([
                'comanda' => 'La comanda está cerrada y ya no se puede modificar.',
            ]);
        }

        if ($respuesta = $this->rechazarSiEstaCerrada($comanda)) {
            return $respuesta;
        }

        DB::transaction(function () use ($comanda, $detalle) {
            $detalle->producto->devolverStock($detalle->cantidad);
            $detalle->delete();
            $comanda->recalcularTotales();
        });

        return $this->responderProducto($request, $comanda, 'Producto quitado.');
    }

    /**
     * PB-14: cerrar la comanda. Calcula el total, la deja en el historial
     * y libera la mesa (vuelve a azul y queda sin mozo).
     */
    public function cerrar(Request $request, Comanda $comanda): RedirectResponse|JsonResponse
    {
        $this->verificarAcceso($request, $comanda);

        if (! $comanda->estaAbierta()) {
            if ($request->expectsJson()) {
                throw ValidationException::withMessages([
                    'comanda' => 'Esta comanda ya está cerrada. Actualizá la pantalla.',
                ]);
            }
            return $this->rechazarSiEstaCerrada($comanda);
        }

        if (! $comanda->detalles()->exists()) {
            if ($request->expectsJson()) {
                throw ValidationException::withMessages([
                    'comanda' => 'No se puede cerrar una comanda sin productos.',
                ]);
            }
            return $this->volverAComanda($comanda)
                ->withErrors(['comanda' => 'No se puede cerrar una comanda sin productos.']);
        }

        DB::transaction(function () use ($request, $comanda) {
            // Mismo orden de bloqueo que al cambiar estado: primero la mesa.
            $mesa = Mesa::whereKey($comanda->mesa_id)->lockForUpdate()->firstOrFail();
            $actual = Comanda::whereKey($comanda->id)->lockForUpdate()->firstOrFail();
            $this->verificarAcceso($request, $actual);

            if (! $actual->estaAbierta() || ! $actual->detalles()->exists()) {
                throw ValidationException::withMessages([
                    'comanda' => 'La comanda cambió. Actualizá la pantalla antes de cerrarla.',
                ]);
            }

            $actual->recalcularTotales();
            $actual->update(['estado' => 'cerrada', 'cerrada_en' => now()]);
            $mesa->update([
                'ultimo_pedido_en' => null,
                'estado' => 'libre',
                'mozo_id' => null,
                'cantidad_personas' => 0,
                'inicio_espera' => null,
            ]);
        });

        $comanda->refresh();
        if ($request->expectsJson()) {
            return response()->json([
                'mensaje' => 'Comanda cerrada correctamente. La mesa quedó libre.',
                'comanda_cerrada_id' => $comanda->id,
                'html' => app(MesaController::class)->index($request)->render(),
            ])->header('Cache-Control', 'no-store');
        }

        return redirect()->route('mesas')->with('success',
            'Comanda de la mesa '.$comanda->mesa->numero.' cerrada. Total: $'.number_format((float) $comanda->total, 2, ',', '.')
        );
    }

    /** Respuesta parcial para JavaScript; conserva la redirección de los formularios normales. */
    private function responderProducto(Request $request, Comanda $comanda, string $mensaje): RedirectResponse|JsonResponse
    {
        if (! $request->expectsJson()) {
            return $this->volverAComanda($comanda)->with('success', $mensaje);
        }

        $comanda->refresh()->load(['mesa', 'detalles.producto']);
        $productos = Producto::activos()->orderBy('categoria')->orderBy('nombre')->get()->groupBy('categoria');
        $pesos = function ($valor) {
            $valor = (float) $valor;
            return '$'.number_format($valor, fmod($valor, 1) == 0 ? 0 : 2, ',', '.');
        };
        $mesa = $comanda->mesa;

        return response()->json([
            'mensaje' => $mensaje,
            'html' => view('comandas.partials.detalles', [
                'comanda' => $comanda,
                'productos' => $productos,
                'pesos' => $pesos,
                'esLaComandaActual' => false,
            ])->render(),
            'mesa' => [
                'id' => $mesa->id,
                'estado' => $mesa->estado,
                'estado_visual' => $mesa->estado_visual,
                'inicio_espera' => $mesa->estaEsperandoPrimerPedido() ? $mesa->inicio_espera->toIso8601String() : '',
                'alerta_espera_en' => $mesa->alerta_espera_en ?? '',
                'desde' => $mesa->ultimo_pedido_en?->getTimestampMs(),
                'total' => $pesos($comanda->total),
            ],
        ])->header('Cache-Control', 'no-store');
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
     * Descuenta stock o corta la operación con un mensaje para el mozo.
     * Al lanzar la excepción dentro de la transacción, no se guarda nada.
     */
    private function descontarOFallar(Producto $producto, int $cantidad): void
    {
        if ($producto->descontarStock($cantidad)) {
            return;
        }

        $disponible = (int) $producto->fresh()->stock;

        throw ValidationException::withMessages([
            'cantidad' => $disponible > 0
                ? "Solo quedan {$disponible} unidades de {$producto->nombre}."
                : "{$producto->nombre} no tiene stock disponible.",
        ]);
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
