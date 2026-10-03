<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MozoAuthController extends Controller
{
    /**
     * Mostrar el formulario de acceso del mozo.
     */
    public function index()
    {
        $mozos = User::where('role', 'mozo')
            ->where('estado', 'activo')
            ->orderBy('name')
            ->get();

        return view('mozo.login', compact('mozos'));
    }

    /**
     * Procesar el acceso del mozo.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $credenciales = [
            'username' => $request->username,
            'role' => 'mozo',
            'estado' => 'activo',
            'password' => $request->password,
        ];

        if (Auth::attempt($credenciales)) {
            $request->session()->regenerate();

            return redirect()->route('mesas');
        }

        return back()
            ->withErrors([
                'password' => 'El usuario o la contraseña son incorrectos.',
            ])
            ->withInput($request->only('username'));
    }

    /**
     * Cerrar sesión del mozo.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('mozo.login');
    }
}