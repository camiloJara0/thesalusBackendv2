<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KardexPlantillasSeeder extends Seeder
{
    public function run()
    {
        // Crear plantilla principal
        $plantilla = DB::table('kardex_plantillas')->insertGetId([
            'nombre'      => 'Kardex General',
            'descripcion' => 'Plantilla general del kardex del paciente con campos básicos de equipamiento y estado.',
            'icono'       => 'fa-file-medical',
            'activo'      => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Crear campos
        $campos = [
            ['nombre' => 'responsable','titulo' => 'Responsable','tipo' => 'text','orden' => 1],
            ['nombre' => 'kit_cateterismo','titulo' => 'Kit Cateterismo','tipo' => 'boolean','orden' => 2],
            ['nombre' => 'rango','titulo' => 'Rango','tipo' => 'text','orden' => 3],
            ['nombre' => 'kit_cambioSonda','titulo' => 'Kit Cambio Sonda','tipo' => 'boolean','orden' => 4],
            ['nombre' => 'kit_gastro','titulo' => 'Kit Gastro','tipo' => 'boolean','orden' => 5],
            ['nombre' => 'traqueo','titulo' => 'Traqueo','tipo' => 'boolean','orden' => 6],
            ['nombre' => 'equipos_biomedicos','titulo' => 'Equipos Biomédicos','tipo' => 'textarea', 'orden' => 7],
            ['nombre' => 'oxigeno','titulo' => 'Oxígeno','tipo' => 'boolean','orden' => 8],
            ['nombre' => 'estado','titulo' => 'Estado','tipo' => 'select','orden' => 9, 'opciones' => ['Activo', 'Inactivo', 'Fallecido']],
            ['nombre' => 'vm','titulo' => 'VM (Ventilador Mecánico)','tipo' => 'text','orden' => 10],
            ['nombre' => 'ultimoCambio','titulo' => 'Último Cambio','tipo' => 'date','orden' => 11],
        ];

        foreach ($campos as $campo) {
            $campoId = DB::table('kardex_campos')->insertGetId([
                'nombre'       => $campo['nombre'],
                'titulo'       => $campo['titulo'],
                'tipo'         => $campo['tipo'],
                'opciones'     => $campo['opciones'] ?? null,
                'activo'       => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // Asignar campo a la plantilla
            DB::table('kardex_plantilla_campos')->insert([
                'id_plantilla' => $plantilla,
                'id_campo'     => $campoId,
                'orden'        => $campo['orden'],
                'requerido'    => false,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}
