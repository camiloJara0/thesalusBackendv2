<?php

namespace Tests\Feature;

use App\Models\Analisis;
use App\Services\PdfAlmacenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * NOTA DE EJECUCIÓN: usa RefreshDatabase. Ejecutar SOLO contra la base
 * de pruebas dedicada (thesalus_pruebas):
 *   DB_DATABASE=thesalus_pruebas vendor/bin/phpunit --filter=PdfAlmacenTest
 * NUNCA ejecutar sobre la base de desarrollo/producción.
 */
class PdfAlmacenTest extends TestCase
{
    use RefreshDatabase;

    private PdfAlmacenService $almacen;

    protected function setUp(): void
    {
        parent::setUp();

        // Simula el disco S3 con credenciales presentes
        config([
            'filesystems.disks.s3.key' => 'test-key',
            'filesystems.disks.s3.secret' => 'test-secret',
            'filesystems.disks.s3.bucket' => 'test-bucket',
            'filesystems.disks.s3.region' => 'us-east-1',
            'exports.pdf.reutilizar' => true,
        ]);
        Storage::fake('s3');

        $this->almacen = app(PdfAlmacenService::class);
    }

    public function test_disponible_con_configuracion(): void
    {
        $this->assertTrue($this->almacen->disponible());
    }

    public function test_no_disponible_sin_credenciales(): void
    {
        config(['filesystems.disks.s3.key' => null]);

        $servicio = new PdfAlmacenService();

        // La disponibilidad está cacheada por proceso; nueva instancia
        // re-evalúa con la configuración actual
        $this->assertFalse($servicio->disponible());
    }

    public function test_existe_con_referencia_y_archivo(): void
    {
        $analisis = Analisis::factory()->create();
        $clave = $this->almacen->clave($analisis->id);

        Storage::disk('s3')->put($clave, 'contenido');

        $this->assertFalse($this->almacen->existe($analisis)); // sin referencia en BD

        $analisis->update(['pdf_almacenado' => $clave]);

        $this->assertTrue($this->almacen->existe($analisis));
    }

    public function test_invalidar_limpia_referencia(): void
    {
        $analisis = Analisis::factory()->create();
        $clave = $this->almacen->clave($analisis->id);

        $analisis->update(['pdf_almacenado' => $clave]);
        $this->almacen->invalidar($analisis->id);

        $this->assertNull($analisis->fresh()->pdf_almacenado);
        $this->assertNull($analisis->fresh()->pdf_almacenado_at);
        $this->assertFalse($this->almacen->existe($analisis->fresh()));
    }

    public function test_descargar_a_escribe_archivo_local(): void
    {
        $analisis = Analisis::factory()->create();
        $clave = $this->almacen->clave($analisis->id);

        Storage::disk('s3')->put($clave, 'contenido-pdf');
        $analisis->update(['pdf_almacenado' => $clave]);

        $destino = storage_path('app/prueba_descarga_' . uniqid() . '.pdf');
        $ruta = $this->almacen->descargarA($analisis, $destino);

        $this->assertNotNull($ruta);
        $this->assertFileExists($ruta);
        $this->assertSame('contenido-pdf', file_get_contents($ruta));

        @unlink($destino);
    }
}
