<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $secciones = [
        'Backoffice_view',
        'Backoffice_salud',
        'Backoffice_bd',
        'Backoffice_errores',
        'Backoffice_trabajos',
        'Backoffice_exportaciones',
        'Backoffice_auditoria',
        'Backoffice_metricas',
        'Backoffice_usuarios',
        'Backoffice_limpieza',
        'Backoffice_respaldos',
    ];

    public function up(): void
    {
        foreach ($this->secciones as $nombre) {
            DB::table('secciones')->insertOrIgnore(['nombre' => $nombre]);
        }
    }

    public function down(): void
    {
        DB::table('secciones')->whereIn('nombre', $this->secciones)->delete();
    }
};
