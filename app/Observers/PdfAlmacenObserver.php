<?php

namespace App\Observers;

use App\Models\Analisis;
use App\Services\PdfAlmacenService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Observador transversal del repositorio de PDFs (S3):
 *
 * - Al CREARSE un análisis: programa la generación+almacenamiento del
 *   PDF (cola, no bloquea el guardado).
 * - Al MODIFICARSE el contenido exportable del análisis: invalida el
 *   PDF almacenado y programa su regeneración.
 * - Al CREARSE/MODIFICARSE/BORRARSE un documento relacionado
 *   (diagnósticos, antecedentes, enfermedad, examen físico, plan de
 *   manejo, nota, descripciones de nota, terapia): invalida el PDF del
 *   análisis afectado para que se regenere en su próximo uso.
 *
 * Nunca lanza excepciones: la auditoría de contenido jamás interrumpe
 * la operación principal.
 */
class PdfAlmacenObserver
{
    /** Campos del análisis que afectan el contenido del PDF */
    private const CAMPOS_CONTENIDO = [
        'motivo',
        'analisis',
        'observacion',
        'tratamiento',
        'tipoAnalisis',
        'id_servicio',
        'id_medico',
    ];

    public function saved(Model $model): void
    {
        try {
            $almacen = app(PdfAlmacenService::class);

            if ($model instanceof Analisis) {
                $cambioContenido = $model->wasRecentlyCreated
                    || $model->wasChanged(self::CAMPOS_CONTENIDO);

                if ($cambioContenido) {
                    $almacen->invalidar($model->id);
                    $almacen->programar($model->id);
                }

                return;
            }

            // Documento relacionado: invalida el PDF del análisis afectado
            $idAnalisis = $model->id_analisis
                ?? $model->nota?->id_analisis
                ?? null;

            if ($idAnalisis) {
                $almacen->invalidar($idAnalisis);
            }
        } catch (\Throwable $e) {
            Log::warning('Observer de PDFs no pudo procesar el evento', [
                'modelo' => get_class($model),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleted(Model $model): void
    {
        try {
            if ($model instanceof Analisis) {
                return; // el PDF quedó huérfano; limpieza futura opcional
            }

            $idAnalisis = $model->id_analisis
                ?? $model->nota?->id_analisis
                ?? null;

            if ($idAnalisis) {
                app(PdfAlmacenService::class)->invalidar($idAnalisis);
            }
        } catch (\Throwable $e) {
            Log::warning('Observer de PDFs no pudo invalidar tras eliminar', [
                'modelo' => get_class($model),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
