<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MetricasFlush extends Command
{
    protected $signature = 'metricas:flush {fecha? : Fecha a consolidar (Y-m-d), por defecto ayer}';

    protected $description = 'Consolida los contadores en cache hacia metricas_api_dias y limpia la cache';

    public function handle(): int
    {
        $fecha = Carbon::parse($this->argument('fecha') ?? now()->subDay()->toDateString());
        $prefijo = 'metricas:api:' . $fecha->toDateString();

        $solicitudes = (int) Cache::pull($prefijo . ':solicitudes', 0);
        $errores4 = (int) Cache::pull($prefijo . ':4xx', 0);
        $errores5 = (int) Cache::pull($prefijo . ':5xx', 0);
        $latenciaSum = (int) Cache::pull($prefijo . ':latencia_sum', 0);
        $latenciaN = (int) Cache::pull($prefijo . ':latencia_n', 0);

        $promedio = $latenciaN > 0 ? (int) round($latenciaSum / $latenciaN) : 0;

        if ($solicitudes === 0 && $errores4 === 0 && $errores5 === 0 && $latenciaN === 0) {
            $this->info("Sin contadores para {$fecha->toDateString()}.");
            return self::SUCCESS;
        }

        DB::table('metricas_api_dias')->updateOrInsert(
            ['fecha' => $fecha->toDateString()],
            [
                'solicitudes' => $solicitudes,
                'errores_4xx' => $errores4,
                'errores_5xx' => $errores5,
                'latencia_promedio_ms' => $promedio,
                'updated_at' => now(),
            ]
        );

        $this->info("Consolidadas metricas del {$fecha->toDateString()}: {$solicitudes} solicitudes, {$errores4} 4xx, {$errores5} 5xx, {$promedio} ms promedio.");

        return self::SUCCESS;
    }
}
