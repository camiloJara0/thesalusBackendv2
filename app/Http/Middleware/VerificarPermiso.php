<?php

namespace App\Http\Middleware;

use App\Support\Acceso;
use Closure;
use Illuminate\Http\Request;

class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string $permiso)
    {
        if (!Acceso::tienePermiso($permiso)) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permiso para esta sección del backoffice',
            ], 403);
        }

        return $next($request);
    }
}
