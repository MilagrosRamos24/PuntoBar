<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use Illuminate\Http\Request;

class AdminMesaController extends Controller
{
    /**
     * Mostrar todas las mesas.
     */
    public function index()
    {
        $mesas = Mesa::with('mozo')
            ->orderBy('numero')
            ->get();

        return view('admin.mesas.index', compact('mesas'));
    }

    /**
     * Mostrar formulario para crear una mesa.
     */
    public function create()
    {
        return view('admin.mesas.create');
    }

    /**
     * Guardar una nueva mesa.
     */
    public function store(Request $request)
    {
        $request->validate([
            'numero' => 'required|integer|min:1|unique:mesas,numero',
        ]);

        Mesa::create([
            'numero' => $request->numero,
            'estado' => 'libre',
            'mozo_id' => null,
            'cantidad_personas' => 0,
        ]);

        return redirect()
            ->route('admin.mesas.index')
            ->with('success', 'Mesa creada correctamente.');
    }

    /**
     * Mostrar formulario para editar una mesa.
     */
    public function edit(Mesa $mesa)
    {
        return view('admin.mesas.edit', compact('mesa'));
    }

    /**
     * Actualizar una mesa.
     */
    public function update(Request $request, Mesa $mesa)
    {
        $request->validate([
            'numero' => 'required|integer|min:1|unique:mesas,numero,' . $mesa->id,
        ]);

        $mesa->update([
            'numero' => $request->numero,
        ]);

        return redirect()
            ->route('admin.mesas.index')
            ->with('success', 'Mesa actualizada correctamente.');
    }

    /**
     * Eliminar una mesa.
     */
    public function destroy(Mesa $mesa)
    {
        $mesa->delete();

        return redirect()
            ->route('admin.mesas.index')
            ->with('success', 'Mesa eliminada correctamente.');
    }
}