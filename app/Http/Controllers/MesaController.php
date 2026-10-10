<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MesaController extends Controller
{
    public function index(Request $request)
{
    $usuario = $request->user();

    // El filtro solo se aplica a los mozos.
    $soloMisMesas = $usuario->role === 'mozo'
        && $request->query('vista') === 'mis-mesas';

    $consulta = Mesa::with([
        'mozo',
        'comandaAbierta.detalles.producto',
    ]);

    if ($soloMisMesas) {
        $consulta->where('mozo_id', $usuario->id);
    }

    $mesas = $consulta
        ->orderBy('numero')
        ->get();

    $productos = Producto::disponibles()
        ->orderBy('categoria')
        ->orderBy('nombre')
        ->get()
        ->groupBy('categoria');

    $comandaParaAbrir = $request->integer('comanda') ?: null;

    return view('mesas.index', compact(
        'mesas',
        'productos',
        'comandaParaAbrir',
        'soloMisMesas'
    ));
}
    public function show(Request $request, Mesa $mesa)
    {
        $this->verificarAcceso($request, $mesa);

        $mesa->load('mozo');

        return view('mesas.show', compact('mesa'));
    }

    public function cambiarEstado(Request $request, Mesa $mesa)
    {
        $this->verificarAcceso($request, $mesa);

        $datos = $request->validate([
            'estado' => [
                'required',
                Rule::in(array_keys(Mesa::ESTADOS)),
            ],
        ]);

        $mesa->update($datos);

        return redirect()
            ->route('mesas')
            ->with('success', 'Estado actualizado correctamente.');
    }

    /**
     * Tomar una mesa libre y registrar al mozo encargado.
     */
    public function atender(Request $request, Mesa $mesa)
    {
        $usuario = $request->user();

        abort_unless(
            $usuario &&
            $usuario->role === 'mozo' &&
            $usuario->estado === 'activo',
            403,
            'No tenés permiso para atender esta mesa.'
        );

        DB::transaction(function () use ($mesa, $usuario) {
            $mesaActual = Mesa::whereKey($mesa->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($mesaActual->estado !== 'libre') {
                throw ValidationException::withMessages([
                    'mesa' => 'La mesa ya no está libre. Actualizá la pantalla.',
                ]);
            }

            if (
                $mesaActual->mozo_id !== null &&
                (int) $mesaActual->mozo_id !== (int) $usuario->id
            ) {
                throw ValidationException::withMessages([
                    'mesa' => 'El administrador asignó esta mesa a otro mozo.',
                ]);
            }

            if ($mesaActual->comandaAbierta()->exists()) {
                throw ValidationException::withMessages([
                    'mesa' => 'Esta mesa tiene una comanda abierta y no se puede tomar.',
                ]);
            }

            $mesaActual->update([
                'mozo_id' => $usuario->id,
                'estado' => 'ocupada',
                'inicio_espera' => now(),
                ]);
        });

        return redirect()
            ->route('mesas')
            ->with(
                'success',
                'Ahora sos el encargado de la mesa '.$mesa->numero.'.'
            );
    }

    /**
     * Permitir acceso al administrador o al mozo asignado.
     */
    private function verificarAcceso(Request $request, Mesa $mesa): void
    {
        $usuario = $request->user();

        $esAdministrador = $usuario->role === 'admin';

        $esMozoAsignado =
            $usuario->role === 'mozo' &&
            (int) $mesa->mozo_id === (int) $usuario->id;

        abort_unless(
            $esAdministrador || $esMozoAsignado,
            403,
            'Esta mesa no está asignada a tu usuario.'
        );
    }
}