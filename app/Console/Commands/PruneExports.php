<?php

namespace App\Console\Commands;

use App\Models\Export;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PruneExports extends Command
{
    protected $signature = 'exports:prune {--horas=24}';

    protected $description = 'Elimina exportaciones antiguas y sus archivos ZIP';

    public function handle()
    {
        $horas = (int) $this->option('horas');
        $corte = now()->subHours($horas);

        $exports = Export::where('created_at', '<', $corte)->get();

        foreach ($exports as $export) {
            if ($export->zip_path && File::exists($export->zip_path)) {
                File::delete($export->zip_path);
            }

            $directorio = storage_path('app/exports/' . $export->id);

            if (File::isDirectory($directorio)) {
                File::deleteDirectory($directorio);
            }

            $export->delete();
        }

        $this->info('Exportaciones podadas: ' . $exports->count());

        return 0;
    }
}
