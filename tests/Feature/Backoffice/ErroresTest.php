<?php

namespace Tests\Feature\Backoffice;

use App\Models\ErrorLog;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\WithApiUser;

class ErroresTest extends TestCase
{
    use WithApiUser;

    private function seedError(array $atributos = []): ErrorLog
    {
        return ErrorLog::create(array_merge([
            'level' => 'error',
            'message' => 'Error de prueba',
            'fingerprint' => sha1('fingerprint-unico-' . uniqid()),
            'context' => ['url' => '/api/v1/test', 'method' => 'GET'],
            'occurred_at' => now(),
        ], $atributos));
    }

    public function test_index_lista_errores_para_superadmin(): void
    {
        $this->seedError(['message' => 'Primer error']);
        $this->seedError(['message' => 'Segundo error']);

        $token = $this->loginAsSuperAdmin();

        $response = $this->getJson('/api/v1/backoffice/errores', $this->authHeaders($token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_index_filtra_por_level(): void
    {
        $this->seedError(['level' => 'error']);
        $this->seedError(['level' => 'critical']);

        $token = $this->loginAsSuperAdmin();

        $response = $this->getJson('/api/v1/backoffice/errores?level=critical', $this->authHeaders($token));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertSame('critical', $response->json('data.data.0.level'));
    }

    public function test_index_devuelve_403_para_admin(): void
    {
        $token = $this->actingAsAdmin();

        $this->getJson('/api/v1/backoffice/errores', $this->authHeaders($token))
            ->assertStatus(403);
    }

    public function test_show_devuelve_detalle_o_404(): void
    {
        $error = $this->seedError(['message' => 'Detalle unico']);
        $token = $this->loginAsSuperAdmin();

        $this->getJson("/api/v1/backoffice/errores/{$error->id}", $this->authHeaders($token))
            ->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['message' => 'Detalle unico']]);

        $this->getJson('/api/v1/backoffice/errores/999999', $this->authHeaders($token))
            ->assertStatus(404);
    }

    public function test_destroy_purga_solo_lo_filtrado(): void
    {
        $this->seedError(['level' => 'error']);
        $this->seedError(['level' => 'critical']);
        $this->seedError(['level' => 'critical']);

        $token = $this->loginAsSuperAdmin();

        $response = $this->deleteJson('/api/v1/backoffice/errores?level=critical', [], $this->authHeaders($token));

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'eliminados' => 2]);

        $this->assertSame(1, ErrorLog::count());
        $this->assertSame('error', ErrorLog::first()->level);
    }

    public function test_destroy_requiere_al_menos_un_filtro(): void
    {
        $this->seedError();
        $token = $this->loginAsSuperAdmin();

        $this->deleteJson('/api/v1/backoffice/errores', [], $this->authHeaders($token))
            ->assertStatus(422);

        $this->assertSame(1, ErrorLog::count());
    }

    public function test_reportable_captura_excepciones_en_error_logs(): void
    {
        report(new \RuntimeException('fallo controlado para test'));

        $this->assertDatabaseHas('error_logs', [
            'message' => 'fallo controlado para test',
            'level' => 'error',
        ]);

        $error = ErrorLog::where('message', 'fallo controlado para test')->first();
        $this->assertNotNull($error);
        $this->assertSame('RuntimeException', $error->context['exception']);
        $this->assertNotEmpty($error->fingerprint);
    }

    public function test_reportable_enmascara_passwords_en_context(): void
    {
        request()->merge(['password' => 'secreto123', 'nombre' => 'Juan']);

        report(new \RuntimeException('error con payload sensible'));

        $error = ErrorLog::where('message', 'error con payload sensible')->first();

        $this->assertNotNull($error);
        $this->assertSame('***', $error->context['payload']['password']);
        $this->assertSame('Juan', $error->context['payload']['nombre']);
    }
}
