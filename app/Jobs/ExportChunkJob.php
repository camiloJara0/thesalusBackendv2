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

class ExportChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public $tries = 2;

    public $backoff = 30;

    public function __construct(
        public int $exportId,
        public array $ids,
        public string $servicio
    ) {
    }

    public function handle(PdfExportService $servicios): void
    {
        $export = Export::find($this->exportId);

        if (!$export || $export->estado === 'fallida') {
            return;
        }

        $directorio = storage_path('app/exports/' . $this->exportId);

        if (!File::isDirectory($directorio)) {
            File::makeDirectory($directorio, 0755, true);
        }

        $analisisList = $servicios->cargarAnalisis($this->ids);

        if ($analisisList->isEmpty()) {
            Export::where('id', $this->exportId)->increment('completados', count($this->ids));
        } else {
            $contexto = $servicios->construirContexto($analisisList);
            $servicios->renderizarLista($analisisList, $this->servicio, $directorio, $contexto);
            Export::where('id', $this->exportId)->increment('completados', $analisisList->count());
        }

        $debeCerrar = Export::where('id', $this->exportId)
            ->where('estado', 'procesando')
            ->whereColumn('completados', '>=', 'total')
            ->update(['estado' => 'comprimiendo']);

        if ($debeCerrar) {
            ExportZipCloseJob::dispatch($this->exportId);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Export::where('id', $this->exportId)->update(['estado' => 'fallida']);
    }
}
