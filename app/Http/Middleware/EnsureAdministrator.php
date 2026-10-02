<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsureAdministrator {
    public function handle(Request $request, Closure $next) {
        if (! $request->user()) {
            return redirect()->route('admin.login');
        }
        abort_unless($request->user()->role === 'admin', 403, 'Acceso exclusivo para administradores.');
        return $next($request);
    }
}
