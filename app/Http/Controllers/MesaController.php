<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MesaController extends Controller
{
    public function index(Request $request)
    {
        $mesas = Mesa::with(['mozo', 'comandaAbierta.detalles.producto'])
            ->orderBy('numero')
            ->get();

        // Catálogo para agregar productos desde la ventana de la comanda.
        $productos = Producto::disponibles()
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get()
            ->groupBy('categoria');

        // Si viene ?comanda=ID, la ventana de esa comanda se abre al cargar la página.
        $comandaParaAbrir = $request->integer('comanda') ?: null;

        return view('mesas.index', compact('mesas', 'productos', 'comandaParaAbrir'));
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
