<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controla el acceso a las rutas según el rol guardado en users.role.
 *
 * Uso en rutas:
 *   ->middleware(VerificarRol::class . ':admin')        solo administrador
 *   ->middleware(VerificarRol::class . ':mozo')         solo mozo
 *   ->middleware(VerificarRol::class . ':admin,mozo')   administrador y mozo
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        // Sin sesión iniciada: al login que corresponde según la sección.
        if (! $usuario) {
            if ($request->is('admin', 'admin/*')) {
                return redirect()->route('admin.login');
            }

            if ($request->is('mozo', 'mozo/*')) {
                return redirect()->route('mozo.login');
            }

            return redirect()->route('home');
        }

        // Un mozo dado de baja pierde el acceso aunque tenga la sesión abierta.
        if ($usuario->role === 'mozo' && $usuario->estado === 'inactivo') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('mozo.login')
                ->withErrors(['password' => 'Tu usuario está inactivo. Consultá con el administrador.']);
        }

        // Con sesión, pero sin permiso para esta ruta
        // (por ejemplo, un mozo que escribe /admin/panel en la barra del navegador).
        if (! in_array($usuario->role, $roles, true)) {
            abort(403, 'No tenés permiso para acceder a esta sección.');
        }

        $response = $next($request);

        // Las páginas protegidas no se guardan en la caché del navegador.
        // Así, después de cerrar sesión, el botón "Atrás" no vuelve a mostrarlas.
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}
