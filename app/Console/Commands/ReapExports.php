<?php

namespace App\Console\Commands;

use App\Services\ExportConcurrencyService;
use Illuminate\Console\Command;

class ReapExports extends Command
{
    protected $signature = 'exports:reap';

    protected $description = 'Marca como fallidas las exportaciones activas vencidas para liberar cupos';

    public function handle(ExportConcurrencyService $concurrencia)
    {
        $cantidad = $concurrencia->marcarVencidas();

        $this->info('Exportaciones vencidas marcadas como fallidas: ' . $cantidad);

        return 0;
    }
}
