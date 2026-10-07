<?php

namespace App\Http\Controllers;

use App\Models\Comanda;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminComandaController extends Controller
{
    /**
     * Módulo IV: listado e historial de comandas (solo administrador).
     * Muestra mesa, mozo, fecha, estado y total de cada comanda,
     * con filtros por estado, fecha y mozo.
     */
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'estado' => ['nullable', Rule::in(array_keys(Comanda::ESTADOS))],
            'fecha' => ['nullable', 'date'],
            'mozo_id' => ['nullable', 'integer'],
        ]);

        $comandas = Comanda::with(['mesa', 'mozo'])
            ->withSum('detalles', 'cantidad')
            ->when($filtros['estado'] ?? null, fn ($consulta, $estado) => $consulta->where('estado', $estado))
            ->when($filtros['fecha'] ?? null, fn ($consulta, $fecha) => $consulta->whereDate('fecha', $fecha))
            ->when($filtros['mozo_id'] ?? null, fn ($consulta, $mozoId) => $consulta->where('mozo_id', $mozoId))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $mozos = User::where('role', 'mozo')->orderBy('name')->get(['id', 'name']);

        return view('admin.comandas.index', compact('comandas', 'mozos', 'filtros'));
    }
}
