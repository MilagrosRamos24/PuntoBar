<?php

namespace App\Http\Controllers;

use App\Models\Comanda;
use App\Models\ConfiguracionDescuento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDescuentoController extends Controller
{
    /**
     * Pantalla para configurar el descuento por consumo (solo administrador).
     */
    public function edit(): View
    {
        $configuracion = ConfiguracionDescuento::actual();
        $comandasAbiertas = Comanda::abiertas()->count();

        return view('admin.descuento.edit', compact('configuracion', 'comandasAbiertas'));
    }

    /**
     * Guarda la configuración y recalcula las comandas abiertas.
     * Las comandas cerradas conservan el descuento con el que se cerraron.
     */
    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'monto_tope' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'porcentaje' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'monto_tope.required' => 'Ingresá el monto tope.',
            'monto_tope.numeric' => 'El monto tope tiene que ser un número.',
            'monto_tope.min' => 'El monto tope no puede ser negativo.',
            'porcentaje.required' => 'Ingresá el porcentaje de descuento.',
            'porcentaje.numeric' => 'El porcentaje tiene que ser un número.',
            'porcentaje.min' => 'El porcentaje tiene que ser mayor a 0.',
            'porcentaje.max' => 'El porcentaje no puede superar el 100 %.',
        ]);

        DB::transaction(function () use ($datos) {
            ConfiguracionDescuento::actual()->update([
                'monto_tope' => $datos['monto_tope'],
                'porcentaje' => $datos['porcentaje'],
                'activo' => (bool) ($datos['activo'] ?? false),
            ]);

            Comanda::abiertas()->get()->each->recalcularTotales();
        });

        return redirect()
            ->route('admin.descuento.edit')
            ->with('success', 'Configuración guardada. Las comandas abiertas se recalcularon.');
    }
}
