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

class ExportZipCloseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public $tries = 2;

    public $backoff = 30;

    public function __construct(public int $exportId)
    {
    }

    public function handle(PdfExportService $servicios): void
    {
        $export = Export::find($this->exportId);

        if (!$export || $export->estado !== 'comprimiendo') {
            return;
        }

        $directorio = storage_path('app/exports/' . $export->id);
        $zipName = $export->zip_name ?: (strtoupper($export->servicio) . '_' . $export->id . '.zip');
        $zipPath = storage_path('app/exports/' . $export->id . '_' . $zipName);

        $servicios->compactarDirectorio($directorio, $zipPath);

        if (File::isDirectory($directorio)) {
            File::deleteDirectory($directorio);
        }

        $export->update([
            'estado' => 'lista',
            'zip_path' => $zipPath,
            'zip_name' => $zipName,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Export::where('id', $this->exportId)->update(['estado' => 'fallida']);
    }
}
