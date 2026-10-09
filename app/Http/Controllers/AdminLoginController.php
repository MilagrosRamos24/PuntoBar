<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AdminLoginController extends Controller
{
    public function create()
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return redirect()->route('mesas');
        }

        return view('admin.login');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'username.required' => 'Ingresá tu usuario.',
            'password.required' => 'Ingresá tu contraseña.',
        ]);

        $username = Str::lower(trim($data['username']));

        $key = 'admin-login:' . hash(
            'sha256',
            $username . '|' . $request->ip()
        );

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()
                ->withErrors([
                    'username' => 'Demasiados intentos. Probá de nuevo en ' .
                        RateLimiter::availableIn($key) .
                        ' segundos.'
                ])
                ->onlyInput('username');
        }

        if (! Auth::attempt([
            'username' => $username,
            'password' => $data['password'],
            'role' => 'admin',
        ])) {

            RateLimiter::hit($key, 60);

            return back()
                ->withErrors([
                    'username' => 'Usuario o contraseña incorrectos.'
                ])
                ->onlyInput('username');
        }

        RateLimiter::clear($key);

        $request->session()->regenerate();

        return redirect()->route('mesas');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}