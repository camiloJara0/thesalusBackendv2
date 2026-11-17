<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class MetricasApi
{
    public function handle($request, Closure $next)
    {
        $inicio = microtime(true);
        $response = $next($request);

        try {
            $fecha = now()->toDateString();
            $prefijo = 'metricas:api:' . $fecha;
            $status = (int) $response->getStatusCode();

            Cache::add($prefijo . ':solicitudes', 0, 172800);
            Cache::add($prefijo . ':4xx', 0, 172800);
            Cache::add($prefijo . ':5xx', 0, 172800);
            Cache::add($prefijo . ':latencia_sum', 0, 172800);
            Cache::add($prefijo . ':latencia_n', 0, 172800);

            Cache::increment($prefijo . ':solicitudes');

            if ($status >= 500) {
                Cache::increment($prefijo . ':5xx');
            } elseif ($status >= 400) {
                Cache::increment($prefijo . ':4xx');
            }

            $ms = (int) round((microtime(true) - $inicio) * 1000);
            Cache::increment($prefijo . ':latencia_sum', $ms);
            Cache::increment($prefijo . ':latencia_n');
        } catch (\Throwable $e) {
            // las metricas jamas interrumpen la peticion
        }

        return $response;
    }
}
