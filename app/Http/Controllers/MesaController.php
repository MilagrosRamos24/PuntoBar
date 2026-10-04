<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use Illuminate\Http\Request;

class MesaController extends Controller
{
    public function index()
    {
        $mesas = Mesa::with('mozo')
            ->orderBy('numero')
            ->get();

        return view('mesas.index', compact('mesas'));
    }

    public function cambiarEstado(Request $request, Mesa $mesa)
    {
        $request->validate([
            'estado' => 'required|in:libre,espera,atendida,alerta',
        ]);

        $mesa->update([
            'estado' => $request->estado,
        ]);

        return redirect()
            ->route('mesas')
            ->with('success', 'Estado de la mesa actualizado correctamente.');
    }
}