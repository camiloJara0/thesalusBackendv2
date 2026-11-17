<?php

namespace App\Http\Middleware;

use App\Support\Acceso;
use Closure;
use Illuminate\Http\Request;

class BackofficeAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!Acceso::esSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso restringido al backoffice',
            ], 403);
        }

        return $next($request);
    }
}
