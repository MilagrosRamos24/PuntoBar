<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controla el acceso a las rutas según el rol guardado en users.role.
 *
 * Uso en rutas:
 *   ->middleware(VerificarRol::class . ':admin')        solo administrador
 *   ->middleware(VerificarRol::class . ':admin,mozo')   administrador y mozo
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        // Sin sesión iniciada:
        // - si intentaba entrar a una sección de administración, al login del administrador;
        // - en cualquier otro caso, a la pantalla de inicio, donde se elige cómo ingresar.
        if (! $usuario) {
            return $request->is('admin', 'admin/*')
                ? redirect()->route('admin.login')
                : redirect()->route('home');
        }

        // Con sesión, pero sin permiso para esta ruta
        // (por ejemplo, un mozo que escribe /admin/panel en la barra del navegador).
        if (! in_array($usuario->role, $roles, true)) {
            abort(403, 'No tenés permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
