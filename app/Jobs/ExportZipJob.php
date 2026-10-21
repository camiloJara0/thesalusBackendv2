<?php

namespace App\Jobs;

use App\Models\Export;
use App\Services\PdfExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

class ExportZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const TAMANO_TANDA = 15;

    public $timeout = 300;

    public $tries = 1;

    public function __construct(public int $exportId)
    {
    }

    public function handle(PdfExportService $servicios): void
    {
        $export = Export::find($this->exportId);

        if (!$export) {
            return;
        }

        $ids = $servicios->queryExport($export->filtros)->pluck('id')->values()->all();
        $total = count($ids);

        if ($total === 0) {
            $export->update(['estado' => 'fallida', 'total' => 0]);

            return;
        }

        $directorio = storage_path('app/exports/' . $export->id);

        if (!File::isDirectory($directorio)) {
            File::makeDirectory($directorio, 0755, true);
        }

        $export->update(['total' => $total, 'estado' => 'procesando']);

        foreach (array_chunk($ids, self::TAMANO_TANDA) as $tanda) {
            ExportChunkJob::dispatch($export->id, $tanda, $export->servicio);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Export::where('id', $this->exportId)->update(['estado' => 'fallida']);
    }
}
