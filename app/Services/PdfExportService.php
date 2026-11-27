<?php

namespace App\Services;

use App\Models\Analisis;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Support\Acceso;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class PdfExportService
{
    /** Tamaño de lote para procesamiento por chunks (memoria acotada) */
    public const TAMANO_LOTE = 15;

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
        } else {
            // Sin filtro de servicio: todos los servicios con plantilla exportable
            $query->whereHas('servicio', function ($q) {
                $q->whereIn('plantilla', self::SERVICIOS);
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

        if (Auth::check()) {
            Acceso::scopeAnalisis($query);
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

    /**
     * Punto ÚNICO de generación de un PDF individual. Reutilizado por:
     * - renderizarLista (exportaciones)
     * - PdfAlmacenService (repositorio S3, generación bajo demanda)
     * No duplicar esta lógica en otros lugares.
     *
     * @return array{nombre: string, bytes: string, servicio: string}|null
     */
    public function renderizarDocumento($analisis, array $contexto, ?string $servicioFiltro = null, array $nombres = []): ?array
    {
        // Servicio del propio registro: permite exportaciones mezcladas
        // cuando no se filtra por servicio. Fallback al filtro global.
        $servicioItem = $analisis->servicio->plantilla ?: ($servicioFiltro ?: 'REGISTRO');

        $datos = $this->datosParaVista($analisis, $contexto, $servicioItem);

        if ($datos === null) {
            return null;
        }

        $pdf = Pdf::loadView($datos['view'], $datos['data']);

        return [
            'nombre' => $this->construirNombre($servicioItem, $analisis, $filtrosEspecial = $this->nombreEspecial($analisis, $servicioItem, $nombres)),
            'bytes' => $pdf->output(),
            'servicio' => $servicioItem,
        ];
    }

    /**
     * Componente de nombre según filtros (profesional o paciente),
     * replicando la convención histórica de nombres de archivo.
     */
    public function nombreEspecial($analisis, string $servicioItem, array $nombres): ?string
    {
        if (!empty($nombres['profesional'][$analisis->id_medico])) {
            return $nombres['profesional'][$analisis->id_medico];
        }

        if (!empty($nombres['paciente'][$analisis->historia?->id_paciente])) {
            return $nombres['paciente'][$analisis->historia?->id_paciente];
        }

        return null;
    }

    /**
     * Nombre de archivo del documento. $nombreEspecial (profesional o
     * paciente) se antepone cuando la exportación filtra por esos criterios.
     */
    public function construirNombre(string $servicioItem, $analisis, ?string $nombreEspecial = null): string
    {
        $fecha = Carbon::parse($analisis->created_at)->format('Ymd');

        if ($nombreEspecial) {
            return sprintf('%s_%s_%s_%s.pdf', strtoupper($servicioItem), $analisis->id, $nombreEspecial, $fecha);
        }

        return sprintf('%s_%s_%s.pdf', strtoupper($servicioItem), $analisis->id, $fecha);
    }

    /**
     * Nombres (profesional/paciente) para la convención de archivos de una
     * exportación filtrada, cuando se reutilizan PDFs almacenados en S3
     * (evita construir el contexto completo solo para nombrar el archivo).
     */
    public function nombresParaFiltros($analisisList, array $filtros): array
    {
        $nombres = ['profesional' => [], 'paciente' => []];

        if (!empty($filtros['id_profesional'])) {
            $ids = $analisisList->pluck('id_medico')->unique()->filter()->values();
            if ($ids->isNotEmpty()) {
                $nombres['profesional'] = DB::table('profesionals')
                    ->join('informacion_users', 'profesionals.id_infoUsuario', '=', 'informacion_users.id')
                    ->whereIn('profesionals.id', $ids)
                    ->select('profesionals.id', 'informacion_users.name')
                    ->pluck('name', 'id')
                    ->map(fn ($n) => $this->nombreArchivoSeguro($n, 'profesional'))
                    ->all();
            }
        } elseif (!empty($filtros['id_paciente'])) {
            $ids = $analisisList->pluck('historia.id_paciente')->unique()->filter()->values();
            if ($ids->isNotEmpty()) {
                $nombres['paciente'] = DB::table('pacientes')
                    ->join('informacion_users', 'pacientes.id_infoUsuario', '=', 'informacion_users.id')
                    ->whereIn('pacientes.id', $ids)
                    ->select('pacientes.id', 'informacion_users.name')
                    ->pluck('name', 'id')
                    ->map(fn ($n) => $this->nombreArchivoSeguro($n, 'paciente'))
                    ->all();
            }
        }

        return $nombres;
    }

    public function renderizarLista($analisisList, ?string $servicio, string $directorio, array $contexto, array $filtros): array
    {
        $pdfFiles = [];

        $nombres = $this->nombresParaFiltros($analisisList, $filtros);

        foreach ($analisisList as $analisis) {
            $documento = $this->renderizarDocumento($analisis, $contexto, $servicio, $nombres);

            if ($documento === null) {
                continue;
            }

            $pdfPath = $directorio . '/' . $documento['nombre'];

            file_put_contents($pdfPath, $documento['bytes']);

            $pdfFiles[] = [
                'path' => $pdfPath,
                'name' => $documento['nombre'],
            ];
        }

        return $pdfFiles;
    }

    /**
     * Renderiza un conjunto de análisis en $directorio REUTILIZANDO los
     * PDFs ya almacenados en el repositorio (S3) y generando bajo demanda
     * los que falten (migración perezosa de análisis históricos).
     * Flujo único compartido por la exportación sync y los chunks async.
     *
     * @return array{archivos: array, procesados: int}
     */
    public function renderizarConReutilizacion(array $ids, ?string $servicioFiltro, array $filtros, string $directorio): array
    {
        $pdfFiles = [];
        $procesados = 0;

        $almacen = app(PdfAlmacenService::class);

        foreach (array_chunk($ids, self::TAMANO_LOTE) as $lote) {
            try {
                $analisisList = $this->cargarAnalisis($lote);

                if ($analisisList->isEmpty()) {
                    $procesados += count($lote);
                    continue;
                }

                $nombres = $this->nombresParaFiltros($analisisList, $filtros);
                $pendientes = collect();

                // 1) Reutilizar los PDFs ya almacenados en S3
                foreach ($analisisList as $analisis) {
                    if ($almacen->disponible() && $almacen->existe($analisis)) {
                        $servicioItem = $analisis->servicio->plantilla ?: 'REGISTRO';
                        $nombre = $this->construirNombre(
                            $servicioItem,
                            $analisis,
                            $this->nombreEspecial($analisis, $servicioItem, $nombres)
                        );

                        if ($almacen->descargarA($analisis, $directorio . '/' . $nombre)) {
                            $pdfFiles[] = [
                                'path' => $directorio . '/' . $nombre,
                                'name' => $nombre,
                            ];
                            $procesados++;
                            continue;
                        }
                    }

                    $pendientes->push($analisis);
                }

                // 2) Generar bajo demanda los que no tienen PDF almacenado
                if ($pendientes->isNotEmpty()) {
                    $contexto = $this->construirContexto($pendientes);

                    foreach ($pendientes as $analisis) {
                        $documento = $this->renderizarDocumento($analisis, $contexto, $servicioFiltro, $nombres);

                        if ($documento !== null) {
                            $ruta = $directorio . '/' . $documento['nombre'];
                            file_put_contents($ruta, $documento['bytes']);

                            $pdfFiles[] = [
                                'path' => $ruta,
                                'name' => $documento['nombre'],
                            ];

                            // Sube inmediatamente a S3 y registra la clave
                            if ($almacen->disponible()) {
                                $almacen->almacenarBytes($analisis, $documento['bytes']);
                            }
                        }

                        $procesados++;
                    }
                }
            } catch (\Throwable $e) {
                // Un lote fallido no detiene la exportación; sus documentos
                // quedan sin archivo y el conteo avanza para permitir el cierre
                \Illuminate\Support\Facades\Log::error('Lote de exportación falló', [
                    'ids' => $lote,
                    'error' => $e->getMessage(),
                ]);
                $procesados += count($lote);
            }
        }

        return ['archivos' => $pdfFiles, 'procesados' => $procesados];
    }

    /**
     * Convierte un nombre en un segmento apto para nombre de archivo.
     * Elimina caracteres inválidos en Windows (/, \, :, *, ?, ", <, >, |),
     * acentos, espacios repetidos y limita el largo, para que
     * file_put_contents no falle al escribir el PDF.
     */
    public function nombreArchivoSeguro(?string $nombre, string $respaldo): string
    {
        $limpio = Str::slug(trim((string) $nombre), '_');

        if ($limpio === '') {
            return $respaldo;
        }

        return Str::limit($limpio, 60, '');
    }

    public function nombreZip(array $filtros): string
    {
        $servicio = strtoupper(!empty($filtros['servicio']) ? $filtros['servicio'] : 'GENERAL');

        if (!empty($filtros['id_profesional'])) {
            $profesional = Profesional::with('infoUsuario')->find($filtros['id_profesional']);
            $nombre = $profesional && $profesional->infoUsuario ? $profesional->infoUsuario->name : 'profesional';

            return $servicio . '_PROFESIONAL_' . Str::slug($nombre, '_') . '.zip';
        }

        if (!empty($filtros['id_paciente'])) {
            $paciente = Paciente::with('infoUsuario')->find($filtros['id_paciente']);
            $nombre = $paciente && $paciente->infoUsuario ? $paciente->infoUsuario->name : 'paciente';

            return $servicio . '_PACIENTE_' . Str::slug($nombre, '_') . '.zip';
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

        // Servicio vacío/null = exportar todos los servicios; si se envía, debe ser válido
        if (!empty($servicio) && !in_array($servicio, self::SERVICIOS, true)) {
            return ['error' => 'Servicio no válido'];
        }

        $ids = $this->queryExport($filtros)->pluck('id')->values()->all();

        $tempDir = storage_path('app/temp_pdf_' . uniqid());

        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Reutiliza PDFs almacenados en S3; genera bajo demanda los faltantes
        $resultado = $this->renderizarConReutilizacion($ids, $servicio, $filtros, $tempDir);
        $pdfFiles = $resultado['archivos'];

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
