<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AyudaController extends Controller
{
    private const ROLES = ['mozo', 'administrador'];

    /**
     * Manual de usuario. Es público (se puede leer antes de ingresar) y muestra la parte del
     * rol elegido con ?rol=mozo o ?rol=administrador. Sin elegir, se muestra la del rol de
     * quien ingresó, y la del mozo si no hay sesión.
     */
    public function index(Request $request): View
    {
        $predeterminado = $request->user()?->role === 'admin' ? 'administrador' : 'mozo';
        $pedido = $request->query('rol');
        $rol = in_array($pedido, self::ROLES, true) ? $pedido : $predeterminado;

        return view('ayuda.index', [
            'rol'    => $rol,
            // El botón del PDF solo aparece si el archivo existe en public/manual/.
            'hayPdf' => file_exists(public_path('manual/manual-de-usuario.pdf')),
        ]);
    }
}