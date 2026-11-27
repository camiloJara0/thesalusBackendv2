<?php

namespace App\Jobs;

use App\Services\PdfAlmacenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Genera el PDF de un análisis y lo almacena en el repositorio S3.
 * Encolado al registrar/modificar un análisis (observer), para no
 * bloquear el guardado. Si falla, el PDF simplemente se generará bajo
 * demanda en la próxima exportación (migración perezosa).
 */
class GenerarPdfAnalisisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180;

    public $tries = 2;

    public $backoff = 60;

    public function __construct(public int $idAnalisis)
    {
    }

    public function handle(PdfAlmacenService $almacen): void
    {
        $almacen->generarYAlmacenar($this->idAnalisis);
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('Generación en cola de PDF de análisis falló (se generará bajo demanda)', [
            'id_analisis' => $this->idAnalisis,
            'error' => $exception->getMessage(),
        ]);
    }
}
