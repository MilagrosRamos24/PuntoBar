<?php

namespace App\Http\Controllers;

use App\Models\NotificacionStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminNotificacionStockController extends Controller
{
    /**
     * Panel de notificaciones: las más recientes primero, leídas y sin leer.
     */
    public function index(): View
    {
        return view('admin.notificaciones.index', [
            'notificaciones' => NotificacionStock::recientes()->paginate(15),
            'noLeidas'       => NotificacionStock::noLeidas()->count(),
        ]);
    }

    /**
     * Marca una notificación como leída. No modifica el stock ni borra nada.
     */
    public function marcarLeida(NotificacionStock $notificacion): RedirectResponse
    {
        $notificacion->marcarLeida();

        return back()->with('success', 'Notificación marcada como leída.');
    }
    public function eliminar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'alcance' => ['required', Rule::in(['seleccionadas', 'todas'])],
            'ids' => ['required_if:alcance,seleccionadas', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:notificaciones_stock,id'],
        ]);

        $consulta = NotificacionStock::query();
        if ($datos['alcance'] === 'seleccionadas') {
            $consulta->whereIn('id', $datos['ids']);
        }

        $cantidad = $consulta->delete();

        return redirect()->route('admin.notificaciones.index')
            ->with('success', "Se eliminaron {$cantidad} notificaciones.");
    }
}
