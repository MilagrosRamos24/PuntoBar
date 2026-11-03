<?php

namespace App\Http\Middleware;

use App\Models\Mesa;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerificarMesaActiva
{
    public function handle(Request $request, Closure $next)
    {
        return DB::transaction(function () use ($request, $next) {
            $parametro = $request->route('mesa');
            $id = $parametro instanceof Mesa ? $parametro->id : $parametro;
            $mesa = Mesa::whereKey($id)->lockForUpdate()->firstOrFail();

            if (! $mesa->activa) {
                return redirect()->route('mesas')
                    ->with('error', 'Esta mesa está desactivada. No se puede abrir una comanda.');
            }

            return $next($request);
        });
    }
}
