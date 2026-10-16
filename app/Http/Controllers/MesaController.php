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

    $productos = Producto::activos()
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

    DB::transaction(function () use ($mesa, $datos) {
        $mesaActual = Mesa::whereKey($mesa->id)
            ->lockForUpdate()
            ->firstOrFail();

        // "reservada" representa Mesa atendida en este proyecto.
        if (in_array($datos['estado'], ['reservada', 'atendida'], true)) {
            $comanda = $mesaActual->comandaAbierta()->first();

            $tieneProductos = $comanda &&
                $comanda->detalles()
                    ->where('cantidad', '>', 0)
                    ->exists();

            if (!$tieneProductos) {
                throw ValidationException::withMessages([
                    'estado' =>
                        'Para marcar la mesa como atendida, cargá al menos un producto en la comanda.',
                ]);
            }

            $datos['inicio_espera'] = null;
        }

        $mesaActual->update($datos);
    });

    return redirect()
        ->route('mesas')
        ->with('success', 'Estado actualizado correctamente.');
}

    /**
 * Tomar una mesa libre y registrar al mozo y las personas.
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

    $datos = $request->validate([
        'cantidad_personas' => ['required', 'integer', 'min:1'],
    ], [
        'cantidad_personas.required' =>
            'Ingresá la cantidad de personas para registrar la atención.',
        'cantidad_personas.integer' =>
            'La cantidad de personas debe ser un número entero.',
        'cantidad_personas.min' =>
            'La cantidad de personas debe ser al menos 1.',
    ]);

    DB::transaction(function () use ($mesa, $usuario, $datos) {
        $mesaActual = Mesa::whereKey($mesa->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($mesaActual->estado !== 'libre') {
            throw ValidationException::withMessages([
                'mesa' =>
                    'La mesa ya no está libre. Actualizá la pantalla.',
            ]);
        }

        if (
            $mesaActual->mozo_id !== null &&
            (int) $mesaActual->mozo_id !== (int) $usuario->id
        ) {
            throw ValidationException::withMessages([
                'mesa' =>
                    'El administrador asignó esta mesa a otro mozo.',
            ]);
        }

        if ($mesaActual->comandaAbierta()->exists()) {
            throw ValidationException::withMessages([
                'mesa' =>
                    'Esta mesa tiene una comanda abierta y no se puede tomar.',
            ]);
        }

        $mesaActual->update([
            'mozo_id' => $usuario->id,
            'cantidad_personas' => (int) $datos['cantidad_personas'],
            'estado' => 'ocupada',
            'inicio_espera' => now(),
        ]);
    });

    return redirect()
        ->route('mesas')
        ->with(
            'success',
            'Atención registrada en la mesa '.$mesa->numero.'.'
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