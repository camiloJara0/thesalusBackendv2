<?php

namespace App\Services;

use App\Jobs\GenerarPdfAnalisisJob;
use App\Models\Analisis;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Repositorio central de PDFs por análisis (AWS S3).
 *
 * Reglas:
 * - Un PDF se genera UNA sola vez (clave estable pdfs-analisis/{id}.pdf)
 *   y se reutiliza en todas las exportaciones posteriores.
 * - Los análisis históricos sin PDF se migran de forma PEREZOSA: se
 *   generan bajo demanda cuando una exportación los solicita, o cuando
 *   el análisis se registra/modifica (GenerarPdfAnalisisJob).
 * - Si S3 no está disponible (credenciales ausentes, fallo de red),
 *   el sistema DEGRADA con elegancia: se genera localmente y la
 *   exportación sigue funcionando como antes.
 * - Cambios en el contenido exportable invalidan el PDF (los observers
 *   de modelos llaman a invalidar/programar).
 */
class PdfAlmacenService
{
    /** Cache estático del estado de disponibilidad del disco */
    private static ?bool $disponible = null;

    public function clave($idAnalisis): string
    {
        return rtrim((string) config('exports.pdf.carpeta', 'pdfs-analisis'), '/') . '/' . $idAnalisis . '.pdf';
    }

    /**
     * Indica si el repositorio está operativo (feature on + disco usable).
     */
    public function disponible(): bool
    {
        if (self::$disponible !== null) {
            return self::$disponible;
        }

        if (!config('exports.pdf.reutilizar', true)) {
            return self::$disponible = false;
        }

        // SDK ausente: el driver s3 no está soportado
        if (!class_exists(\Aws\S3\S3Client::class)) {
            return self::$disponible = false;
        }

        // Sin credenciales configuradas: desactiva silenciosamente
        if (!config('filesystems.disks.s3.key') || !config('filesystems.disks.s3.bucket')) {
            return self::$disponible = false;
        }

        return self::$disponible = true;
    }

    /**
     * Programa la generación+almacenamiento del PDF de un análisis
     * vía cola (no bloquea la operación que lo invoca).
     */
    public function programar($idAnalisis): void
    {
        if (!$this->disponible() || !$idAnalisis) {
            return;
        }

        if (!config('exports.pdf.generar_al_guardar', true)) {
            return;
        }

        GenerarPdfAnalisisJob::dispatch((int) $idAnalisis);
    }

    /**
     * Invalida el PDF almacenado de un análisis (el contenido exportable
     * cambió). El PDF se regenerará en su próximo uso.
     */
    public function invalidar($idAnalisis): void
    {
        if (!$idAnalisis) {
            return;
        }

        try {
            Analisis::where('id', $idAnalisis)->update([
                'pdf_almacenado' => null,
                'pdf_almacenado_at' => null,
            ]);
        } catch (Throwable $e) {
            Log::warning('No se pudo invalidar el PDF del análisis', ['id' => $idAnalisis]);
        }
    }

    /**
     * ¿Existe un PDF almacenado y válido para este análisis?
     */
    public function existe($analisis): bool
    {
        if (empty($analisis->pdf_almacenado)) {
            return false;
        }

        try {
            return (bool) Storage::disk($this->disco())->exists($analisis->pdf_almacenado);
        } catch (Throwable $e) {
            return false; // degradación: se genera localmente
        }
    }

    /**
     * Descarga el PDF almacenado en S3 a un archivo local (streaming,
     * sin cargar el documento completo en memoria).
     *
     * @return string|null ruta local del archivo descargado
     */
    public function descargarA($analisis, string $destino): ?string
    {
        try {
            $stream = Storage::disk($this->disco())->readStream($analisis->pdf_almacenado);

            if (!$stream) {
                return null;
            }

            $ok = (bool) file_put_contents($destino, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            return $ok ? $destino : null;
        } catch (Throwable $e) {
            Log::warning('Fallo al descargar PDF de S3, se regenerará localmente', [
                'id_analisis' => $analisis->id,
                'clave' => $analisis->pdf_almacenado,
            ]);
            return null;
        }
    }

    /**
     * Genera el PDF con la lógica ÚNICA (PdfExportService), lo sube a S3
     * y registra la clave en la base de datos.
     *
     * @param  Analisis|int  $analisis
     * @return bool  true si quedó almacenado
     */
    public function generarYAlmacenar($analisis): bool
    {
        if (!$this->disponible()) {
            return false;
        }

        try {
            if (!$analisis instanceof Analisis) {
                $analisis = Analisis::find($analisis);
            }

            if (!$analisis) {
                return false;
            }

            $exportador = app(PdfExportService::class);
            $lista = $exportador->cargarAnalisis([$analisis->id]);
            $documento = null;

            if ($lista->isNotEmpty()) {
                $contexto = $exportador->construirContexto($lista);
                $documento = $exportador->renderizarDocumento($lista->first(), $contexto);
            }

            if ($documento === null) {
                return false;
            }

            $clave = $this->clave($analisis->id);

            Storage::disk($this->disco())->put($clave, $documento['bytes']);

            try {
                $analisis->update([
                    'pdf_almacenado' => $clave,
                    'pdf_almacenado_at' => now(),
                ]);
            } catch (Throwable $e) {
                Log::error('PDF almacenado en S3 pero la BD no registró la clave', [
                    'id_analisis' => $analisis->id,
                    'clave' => $clave,
                    'error' => $e->getMessage(),
                ]);
            }

            return true;
        } catch (Throwable $e) {
            // Degradación silenciosa: el PDF se generará bajo demanda
            Log::warning('No se pudo almacenar el PDF del análisis en el repositorio', [
                'id_analisis' => $analisis instanceof Analisis ? $analisis->id : $analisis,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Asegura que exista el archivo PDF local de un análisis en el
     * directorio de una exportación: reutiliza S3 si está almacenado;
     * si no, lo genera con la lógica única, lo sube a S3 y registra la
     * clave (migración perezosa de registros históricos).
     *
     * @return string|null nombre del archivo PDF listo en $directorio
     */
    public function asegurar($analisis, string $directorio, ?string $servicioFiltro = null, array $nombres = []): ?string
    {
        try {
            $exportador = app(PdfExportService::class);
            $nombre = $this->nombreLocal($analisis, $servicioFiltro, $nombres);
            $destino = $directorio . '/' . $nombre;

            // 1) Reutilizar el PDF almacenado en S3
            if ($this->existe($analisis)) {
                $descargado = $this->descargarA($analisis, $destino);

                if ($descargado && File::exists($descargado)) {
                    return $nombre;
                }
            }

            // 2) Generar bajo demanda con la lógica única
            $lista = $exportador->cargarAnalisis([$analisis->id]);

            if ($lista->isEmpty()) {
                return null;
            }

            $documento = $exportador->renderizarDocumento(
                $lista->first(),
                $exportador->construirContexto($lista),
                $servicioFiltro,
                $nombres
            );

            if ($documento === null) {
                return null;
            }

            if ($documento['nombre'] !== $nombre) {
                $destino = $directorio . '/' . $documento['nombre'];
            }

            file_put_contents($destino, $documento['bytes']);

            // 3) Subir inmediatamente a S3 y registrar la referencia
            if ($this->disponible()) {
                $this->almacenar($analisis, $documento['bytes']);
            }

            return basename($destino);
        } catch (Throwable $e) {
            Log::warning('No se pudo asegurar el PDF de un análisis para exportación', [
                'id_analisis' => $analisis->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function disco(): string
    {
        return (string) config('exports.pdf.disco', 's3');
    }

    private function nombreLocal($analisis, ?string $servicioFiltro, array $nombres): string
    {
        $exportador = app(PdfExportService::class);
        $servicioItem = $analisis->servicio->plantilla ?: ($servicioFiltro ?: 'REGISTRO');

        return $exportador->construirNombre(
            $servicioItem,
            $analisis,
            $exportador->nombreEspecial($analisis, $servicioItem, $nombres)
        );
    }

    /**
     * Sube bytes de PDF al repositorio y registra la clave en la BD.
     * La subida S3 y el registro en BD van en try/catch SEPARADOS: un
     * fallo de base de datos (ej. campo no fillable / columna ausente)
     * no debe enmascararse como fallo de subida.
     */
    public function almacenarBytes($analisis, string $bytes): void
    {
        $clave = $this->clave($analisis->id);

        try {
            Storage::disk($this->disco())->put($clave, $bytes);
        } catch (Throwable $e) {
            Log::warning('Fallo la subida del PDF al repositorio S3 (se seguirá generando bajo demanda)', [
                'id_analisis' => $analisis->id,
                'clave' => $clave,
                'error' => $e->getMessage(),
            ]);
            return; // sin archivo en S3 no hay nada que registrar
        }

        try {
            $actualizado = $analisis->update([
                'pdf_almacenado' => $clave,
                'pdf_almacenado_at' => now(),
            ]);

            if (!$actualizado) {
                Log::warning('El registro del PDF no aplicó cambios en la BD', [
                    'id_analisis' => $analisis->id,
                    'clave' => $clave,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Fallo al registrar la clave del PDF en la BD (el PDF SÍ quedó en S3)', [
                'id_analisis' => $analisis->id,
                'clave' => $clave,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function almacenar($analisis, string $bytes): void
    {
        $this->almacenarBytes($analisis, $bytes);
    }
}
