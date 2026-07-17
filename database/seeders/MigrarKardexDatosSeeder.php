<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrarKardexDatosSeeder extends Seeder
{
    public function run()
    {
        $kardexRows = DB::table('kardex')->get();

        foreach ($kardexRows as $kardex) {
            $registros = [
                'responsable'       => $kardex->responsable,
                'kit_cateterismo'   => $this->boolToText($kardex->kit_cateterismo),
                'rango'             => $kardex->rango,
                'kit_cambioSonda'   => $this->boolToText($kardex->kit_cambioSonda),
                'kit_gastro'        => $this->boolToText($kardex->kit_gastro),
                'traqueo'           => $this->boolToText($kardex->traqueo),
                'equipos_biomedicos'=> $kardex->equipos_biomedicos,
                'oxigeno'           => $this->boolToText($kardex->oxigeno),
                'estado'            => $kardex->estado,
                'vm'                => $kardex->vm,
                'ultimoCambio'      => $kardex->ultimoCambio,
            ];

            foreach ($registros as $nombre => $valor) {
                if ($valor === null || $valor === '') {
                    continue;
                }

                $campo = DB::table('kardex_campos')->where('nombre', $nombre)->first();
                if (!$campo) {
                    continue;
                }

                DB::table('kardex_registros')->updateOrInsert(
                    ['id_paciente' => $kardex->id_paciente, 'id_campo' => $campo->id],
                    ['valor' => (string) $valor, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    private function boolToText($value): ?string
    {
        if ($value === null) return null;
        return $value ? 'true' : 'false';
    }
}
