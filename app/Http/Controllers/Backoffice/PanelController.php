<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Support\Acceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PanelController extends Controller
{
    public function ping()
    {
        return response()->json([
            'success' => true,
            'rol' => Acceso::usuario()?->rol,
            'permisos_backoffice' => collect(Acceso::permisosEfectivos())
                ->filter(fn ($permiso) => str_starts_with($permiso, 'Backoffice_'))
                ->values(),
        ]);
    }

    public function metricas(Request $request)
    {
        $desde = $request->input('desde', now()->subDays(30)->toDateString());
        $hasta = $request->input('hasta', now()->toDateString());

        $serie = DB::table('metricas_api_dias')
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')
            ->get();

        $solicitudes = (int) $serie->sum('solicitudes');
        $errores4 = (int) $serie->sum('errores_4xx');
        $errores5 = (int) $serie->sum('errores_5xx');

        $resumen = [
            'dias' => $serie->count(),
            'solicitudes' => $solicitudes,
            'errores_4xx' => $errores4,
            'errores_5xx' => $errores5,
            'errores_total' => $errores4 + $errores5,
            'tasa_error_pct' => $solicitudes > 0 ? round((($errores4 + $errores5) / $solicitudes) * 100, 2) : 0,
            'latencia_promedio_ms' => $serie->count() > 0 ? (int) round($serie->avg('latencia_promedio_ms')) : 0,
        ];

        return response()->json([
            'success' => true,
            'rango' => ['desde' => $desde, 'hasta' => $hasta],
            'serie' => $serie,
            'resumen' => $resumen,
        ]);
    }

    public function salud()
    {
        $dbOk = true;
        $dbError = null;
        try {
            DB::select('SELECT 1');
        } catch (\Throwable $e) {
            $dbOk = false;
            $dbError = class_basename($e);
        }

        $logPath = storage_path('logs/laravel.log');
        $logBytes = is_file($logPath) ? (int) filesize($logPath) : 0;

        $total = @disk_total_space(storage_path());
        $libre = @disk_free_space(storage_path());

        $tablas = ['users', 'profesionals', 'citas', 'pacientes', 'historias_clinicas', 'exports', 'error_logs', 'audit_logs', 'jobs', 'failed_jobs'];
        $stats = [];
        foreach ($tablas as $tabla) {
            try {
                $stats[$tabla] = (int) DB::table($tabla)->count();
            } catch (\Throwable $e) {
                $stats[$tabla] = null;
            }
        }

        return response()->json([
            'success' => true,
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'entorno' => config('app.env'),
            'debug' => (bool) config('app.debug'),
            'servidor' => php_uname(),
            'uptime_peticion_ms' => defined('LARAVEL_START') ? (int) round((microtime(true) - LARAVEL_START) * 1000) : null,
            'base_datos' => [
                'conectada' => $dbOk,
                'error' => $dbError,
                'conexion' => config('database.default'),
                'host' => config('database.connections.' . config('database.default') . '.host'),
                'nombre' => config('database.connections.' . config('database.default') . '.database'),
            ],
            'disco' => [
                'total_bytes' => $total ?: null,
                'libre_bytes' => $libre ?: null,
                'uso_pct' => ($total && $libre) ? round((($total - $libre) / $total) * 100, 1) : null,
            ],
            'log' => ['ruta' => 'storage/logs/laravel.log', 'bytes' => $logBytes],
            'cola' => [
                'pendientes' => $dbOk ? (int) DB::table('jobs')->count() : null,
                'fallidos' => $dbOk ? (int) DB::table('failed_jobs')->count() : null,
                'conexion' => config('queue.default'),
            ],
            'cache' => config('cache.default'),
            'tablas' => $stats,
        ]);
    }
}
