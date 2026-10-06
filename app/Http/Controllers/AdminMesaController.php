<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminMesaController extends Controller
{
    public function index()
    {
        $mesas = Mesa::with('mozo')->orderBy('numero')->get();
        return view('admin.mesas.index', compact('mesas'));
    }

    private function formulario(?Mesa $mesa = null): array
    {
        $estados = Mesa::ESTADOS;
        // Conservar estados anteriores hasta que el administrador decida cambiarlos.
        if ($mesa && !array_key_exists($mesa->estado, $estados)) {
            $estados[$mesa->estado] = $mesa->estado_texto . ' (estado anterior)';
        }
        $mozos = User::where('role', 'mozo')->where('estado', 'activo')->orderBy('name')->get();
        return compact('mesa', 'estados', 'mozos');
    }

    public function create()
    {
        return view('admin.mesas.create', $this->formulario());
    }

    private function datos(Request $request, ?Mesa $mesa = null): array
    {
        $estados = array_keys(Mesa::ESTADOS);
        if ($mesa) {
            $estados[] = $mesa->estado;
        }
        $datos = $request->validate([
            'numero' => ['required', 'integer', 'min:1', 'max:2147483647', Rule::unique('mesas', 'numero')->ignore($mesa)],
            'estado' => ['required', Rule::in($estados)],
            'mozo_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(function ($query) use ($mesa) {
                $query->where('role', 'mozo')->where(function ($query) use ($mesa) {
                    $query->where('estado', 'activo');
                    if ($mesa && $mesa->mozo_id) {
                        $query->orWhere('id', $mesa->mozo_id);
                    }
                });
            })],
            'cantidad_personas' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ], [
            'numero.unique' => 'Ya existe una mesa con ese número.',
            'mozo_id.exists' => 'Seleccioná un mozo activo válido.',
            'estado.in' => 'Seleccioná un estado válido.',
            'cantidad_personas.min' => 'La cantidad de personas no puede ser negativa.',
        ]);
        $datos['mozo_id'] = $datos['mozo_id'] ?? null;
        return $datos;
    }

    public function store(Request $request)
    {
        $mesa = Mesa::create($this->datos($request));
        return redirect()->route('admin.mesas.show', $mesa)->with('success', 'Mesa creada correctamente.');
    }

    public function show(Mesa $mesa)
    {
        $mesa->load('mozo');
        return view('admin.mesas.show', compact('mesa'));
    }

    public function edit(Mesa $mesa)
    {
        $mesa->load('mozo');
        return view('admin.mesas.edit', $this->formulario($mesa));
    }

    public function update(Request $request, Mesa $mesa)
    {
        $mesa->update($this->datos($request, $mesa));
        return redirect()->route('admin.mesas.show', $mesa)->with('success', 'Mesa actualizada correctamente.');
    }

    public function destroy(Mesa $mesa)
    {
        $mesa->delete();
        return redirect()->route('admin.mesas.index')->with('success', 'Mesa eliminada correctamente.');
    }
}
