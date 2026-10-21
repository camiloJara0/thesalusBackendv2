<?php

namespace App\Services;

use App\Models\Analisis;
use App\Models\Paciente;
use App\Models\Profesional;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class PdfExportService
{
    public const SERVICIOS = ['Evolucion', 'Trabajo Social', 'Medicina', 'Nota', 'Terapia'];

    public const RELACIONES = [
        'servicio',
        'historia',
        'diagnosticos',
        'enfermedad',
        'examenFisico',
        'medicamentos',
        'procedimientos',
        'nota.descripcionNota',
        'terapia',
        'profesional.infoUsuario',
    ];

    public function queryExport(array $filtros)
    {
        $servicio = $filtros['servicio'] ?? null;
        $fechaInicio = $filtros['fechaInicio'] ?? null;
        $fechaFin = $filtros['fechaFin'] ?? null;
        $idPaciente = $filtros['id_paciente'] ?? null;
        $idProfesional = $filtros['id_profesional'] ?? null;

        $query = Analisis::with(self::RELACIONES);

        if (!empty($servicio)) {
            $query->whereHas('servicio', function ($q) use ($servicio) {
                $q->where('plantilla', $servicio);
            });
        }

        if ($servicio === 'Nota') {
            $query->whereHas('nota', function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereDate('fecha_nota', '>=', $fechaInicio)
                    ->whereDate('fecha_nota', '<=', $fechaFin);
            });
        } elseif ($servicio === 'Terapia') {
            $query->whereHas('terapia', function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereDate('fecha', '>=', $fechaInicio)
                    ->whereDate('fecha', '<=', $fechaFin);
            });
        } else {
            $query->whereBetween('created_at', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ]);
        }

        if (!empty($idProfesional)) {
            $query->whereHas('profesional', function ($q) use ($idProfesional) {
                $q->where('id', $idProfesional);
            });
        }

        if (!empty($idPaciente)) {
            $query->whereHas('historia', function ($q) use ($idPaciente) {
                $q->where('id_paciente', $idPaciente);
            });
        }

        return $query;
    }

    public function cargarAnalisis(array $ids)
    {
        return Analisis::with(self::RELACIONES)
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();
    }

    public function construirContexto($analisisList, bool $completo = true)
    {
        $pacienteIds = $analisisList
            ->pluck('historia.id_paciente')
            ->unique()
            ->filter()
            ->values();

        $profesionalIds = $analisisList
            ->pluck('id_medico')
            ->unique()
            ->filter()
            ->values();

        $pacientes = DB::table('pacientes')
            ->join('informacion_users', 'pacientes.id_infoUsuario', '=', 'informacion_users.id')
            ->join('eps', 'pacientes.id_eps', '=', 'eps.id')
            ->whereIn('pacientes.id', $pacienteIds)
            ->select('pacientes.*', 'informacion_users.*', 'eps.nombre as Eps', 'pacientes.id as _id_paciente')
            ->get()
            ->keyBy('_id_paciente');

        $profesionales = DB::table('profesionals')
            ->join('informacion_users', 'profesionals.id_infoUsuario', '=', 'informacion_users.id')
            ->whereIn('profesionals.id', $profesionalIds)
            ->select('profesionals.*', 'informacion_users.*', 'profesionals.id as _id_profesional')
            ->get()
            ->keyBy('_id_profesional');

        $contexto = [
            'pacientes' => $pacientes,
            'profesionales' => $profesionales,
            'antecedentes' => collect(),
            'convenios' => collect(),
            'diagnosticosCIF' => collect(),
        ];

        if (!$completo) {
            return $contexto;
        }

        $contexto['antecedentes'] = DB::table('antecedentes')
            ->whereIn('id_paciente', $pacienteIds)
            ->get()
            ->groupBy('id_paciente');

        $contexto['convenios'] = DB::table('paciente_has_convenios')
            ->join('convenios', 'paciente_has_convenios.id_convenio', '=', 'convenios.id')
            ->whereIn('paciente_has_convenios.id_paciente', $pacienteIds)
            ->select('convenios.*', 'paciente_has_convenios.id_paciente as _id_paciente')
            ->get()
            ->groupBy('_id_paciente');

        $contexto['diagnosticosCIF'] = DB::table('diagnostico_relacionados')
            ->whereIn('id_analisis', $analisisList->pluck('id'))
            ->get()
            ->groupBy('id_analisis');

        return $contexto;
    }

    private function examenFisicoSinCast($examenFisico)
    {
        if (!$examenFisico) {
            return null;
        }

        $atributos = $examenFisico->getAttributes();
        $atributos['signosVitales'] = $examenFisico->getRawOriginal('signosVitales');

        return (object) $atributos;
    }

    public function datosParaVista($analisis, array $contexto, string $servicio): ?array
    {
        $historia = $analisis->historia;
        $idPaciente = $historia ? $historia->id_paciente : null;

        $paciente = $idPaciente ? ($contexto['pacientes'][$idPaciente] ?? null) : null;
        $profesional = $analisis->id_medico ? ($contexto['profesionales'][$analisis->id_medico] ?? null) : null;
        $diagnosticos = $analisis->diagnosticos;
        $medicamentos = $analisis->medicamentos;
        $procedimientos = $analisis->procedimientos;
        $antecedentes = $idPaciente ? ($contexto['antecedentes'][$idPaciente] ?? collect()) : collect();
        $examenFisico = $this->examenFisicoSinCast($analisis->examenFisico);
        $enfermedades = $analisis->enfermedad;
        $convenios = $idPaciente
            ? ($contexto['convenios'][$idPaciente] ?? collect())->first()
            : null;

        switch ($servicio) {
            case 'Evolucion':
                return [
                    'view' => 'pdf.evolucion',
                    'data' => compact(
                        'paciente',
                        'profesional',
                        'diagnosticos',
                        'analisis',
                        'medicamentos',
                        'convenios',
                        'examenFisico'
                    ),
                ];

            case 'Trabajo Social':
                return [
                    'view' => 'pdf.trabajoSocial',
                    'data' => compact(
                        'paciente',
                        'profesional',
                        'diagnosticos',
                        'analisis',
                        'medicamentos',
                        'convenios'
                    ),
                ];

            case 'Medicina':
                return [
                    'view' => 'pdf.medicina',
                    'data' => compact(
                        'paciente',
                        'profesional',
                        'diagnosticos',
                        'analisis',
                        'antecedentes',
                        'examenFisico',
                        'medicamentos',
                        'procedimientos',
                        'convenios',
                        'enfermedades'
                    ),
                ];

            case 'Nota':
                $nota = $analisis->nota;
                $descripcion = $nota ? $nota->descripcionNota : collect();

                return [
                    'view' => 'pdf.nota',
                    'data' => compact(
                        'nota',
                        'paciente',
                        'profesional',
                        'diagnosticos',
                        'descripcion',
                        'analisis',
                        'convenios'
                    ),
                ];

            case 'Terapia':
                $terapia = $analisis->terapia;
                $diagnosticosCIF = $terapia
                    ? ($contexto['diagnosticosCIF'][$terapia->id_analisis] ?? collect())
                    : collect();

                return [
                    'view' => 'pdf.terapia',
                    'data' => compact(
                        'terapia',
                        'paciente',
                        'profesional',
                        'diagnosticos',
                        'diagnosticosCIF',
                        'analisis',
                        'convenios'
                    ),
                ];

            default:
                return null;
        }
    }

    public function renderizarLista($analisisList, string $servicio, string $directorio, array $contexto): array
    {
        $pdfFiles = [];

        foreach ($analisisList as $analisis) {
            $datos = $this->datosParaVista($analisis, $contexto, $servicio);

            if ($datos === null) {
                continue;
            }

            $pdf = Pdf::loadView($datos['view'], $datos['data']);

            $pdfName = sprintf('%s_%s.pdf', strtoupper($servicio), $analisis->id);
            $pdfPath = $directorio . '/' . $pdfName;

            file_put_contents($pdfPath, $pdf->output());

            $pdfFiles[] = [
                'path' => $pdfPath,
                'name' => $pdfName,
            ];
        }

        return $pdfFiles;
    }

    public function nombreZip(array $filtros): string
    {
        $servicio = strtoupper($filtros['servicio'] ?? 'GENERAL');

        if (!empty($filtros['id_paciente'])) {
            $paciente = Paciente::with('infoUsuario')->find($filtros['id_paciente']);
            $nombre = $paciente && $paciente->infoUsuario ? $paciente->infoUsuario->name : 'paciente';

            return $servicio . '_PACIENTE_' . Str::slug($nombre, '_') . '.zip';
        }

        if (!empty($filtros['id_profesional'])) {
            $profesional = Profesional::with('infoUsuario')->find($filtros['id_profesional']);
            $nombre = $profesional && $profesional->infoUsuario ? $profesional->infoUsuario->name : 'profesional';

            return $servicio . '_PROFESIONAL_' . Str::slug($nombre, '_') . '.zip';
        }

        return $servicio . '_GENERAL.zip';
    }

    public function compactarZip(array $pdfFiles, string $zipPath): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('No se pudo crear el ZIP');
        }

        foreach ($pdfFiles as $pdf) {
            $zip->addFile($pdf['path'], $pdf['name']);
        }

        $zip->close();
    }

    public function compactarDirectorio(string $directorio, string $zipPath): void
    {
        $pdfFiles = [];

        if (File::isDirectory($directorio)) {
            foreach (File::files($directorio) as $archivo) {
                $pdfFiles[] = [
                    'path' => $archivo->getPathname(),
                    'name' => $archivo->getFilename(),
                ];
            }
        }

        $this->compactarZip($pdfFiles, $zipPath);
    }

    public function exportarZip(array $filtros): array
    {
        $servicio = $filtros['servicio'] ?? null;

        if (!in_array($servicio, self::SERVICIOS, true)) {
            return ['error' => 'Servicio no válido'];
        }

        $analisisList = $this->queryExport($filtros)->get();
        $contexto = $this->construirContexto($analisisList);

        $tempDir = storage_path('app/temp_pdf_' . uniqid());

        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $pdfFiles = $this->renderizarLista($analisisList, $servicio, $tempDir, $contexto);

        $zipName = $this->nombreZip($filtros);
        $zipPath = storage_path('app/' . uniqid() . '_' . $zipName);

        $this->compactarZip($pdfFiles, $zipPath);

        foreach ($pdfFiles as $pdf) {
            if (file_exists($pdf['path'])) {
                unlink($pdf['path']);
            }
        }

        @rmdir($tempDir);

        return [
            'zip_path' => $zipPath,
            'zip_name' => $zipName,
        ];
    }

    public function datosExcel(array $filtros): array
    {
        $analisisList = $this->queryExport($filtros)->get();
        $contexto = $this->construirContexto($analisisList, false);

        return $analisisList->map(function ($analisis) use ($contexto) {
            $historia = $analisis->historia;
            $idPaciente = $historia ? $historia->id_paciente : null;
            $pacienteRow = $idPaciente ? ($contexto['pacientes'][$idPaciente] ?? null) : null;
            $profesionalRow = $analisis->id_medico ? ($contexto['profesionales'][$analisis->id_medico] ?? null) : null;
            $nota = $analisis->nota;
            $terapia = $analisis->terapia;

            return [
                'id' => $analisis->id,
                'fecha' => (string) $analisis->created_at,
                'motivo' => $analisis->motivo,
                'observacion' => $analisis->observacion,
                'tratamiento' => $analisis->tratamiento,
                'tipoAnalisis' => $analisis->tipoAnalisis,
                'analisisClinico' => $analisis->analisis,
                'paciente' => $pacienteRow->name ?? null,
                'documento' => $pacienteRow->No_document ?? null,
                'eps' => $pacienteRow->Eps ?? null,
                'profesional' => $profesionalRow->name ?? null,
                'servicio' => $analisis->servicio->name ?? null,
                'plantilla' => $analisis->servicio->plantilla ?? null,
                'nota_fecha' => $nota->fecha_nota ?? null,
                'nota_tipo' => $nota->tipoAnalisis ?? null,
                'terapia_fecha' => $terapia->fecha ?? null,
                'terapia_sesion' => $terapia->sesion ?? null,
                'terapia_objetivos' => $terapia->objetivos ?? null,
                'terapia_evolucion' => $terapia->evolucion ?? null,
            ];
        })->values()->all();
    }
}
