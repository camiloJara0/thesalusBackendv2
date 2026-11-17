<?php

namespace Tests\Feature\Backoffice;

use App\Jobs\ExportZipJob;
use App\Models\Export;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\WithApiUser;

class TrabajosTest extends TestCase
{
    use WithApiUser;

    private function seedJob(string $queue = 'default', bool $reservado = false): void
    {
        DB::table('jobs')->insert([
            'queue' => $queue,
            'payload' => json_encode(['displayName' => 'App\\Jobs\\PruebaJob']),
            'attempts' => 1,
            'reserved_at' => $reservado ? time() : null,
            'available_at' => time(),
            'created_at' => time(),
        ]);
    }

    private function seedFailedJob(string $commandName, string $command, string $exception = 'RuntimeException: fallo'): int
    {
        return DB::table('failed_jobs')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'uuid' => (string) Str::uuid(),
                'displayName' => $commandName,
                'job' => 'Illuminate\Queue\CallQueuedHandler@call',
                'data' => [
                    'commandName' => $commandName,
                    'command' => $command,
                ],
            ]),
            'exception' => $exception,
            'failed_at' => now(),
        ]);
    }

    private function seedExport(int $userId): Export
    {
        return Export::create([
            'user_id' => $userId,
            'servicio' => 'pacientes',
            'filtros' => ['fechaInicio' => '2026-01-01', 'fechaFin' => '2026-12-31'],
            'estado' => 'error',
            'total' => 10,
            'completados' => 3,
        ]);
    }

    public function test_index_devuelve_cola_y_fallidos(): void
    {
        $this->seedJob('default');
        $this->seedJob('exports', reservado: true);
        $this->seedFailedJob(ExportZipJob::class, serialize(new ExportZipJob(1)));

        $token = $this->loginAsSuperAdmin();

        $response = $this->getJson('/api/v1/backoffice/trabajos', $this->authHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('cola.pendientes', 2)
            ->assertJsonPath('cola.reservados', 1);

        $this->assertCount(1, $response->json('fallidos'));
        $this->assertSame(ExportZipJob::class, $response->json('fallidos.0.displayName'));
    }

    public function test_index_devuelve_403_para_admin(): void
    {
        $token = $this->actingAsAdmin();

        $this->getJson('/api/v1/backoffice/trabajos', $this->authHeaders($token))
            ->assertStatus(403);
    }

    public function test_show_failed_devuelve_detalle_o_404(): void
    {
        $id = $this->seedFailedJob(ExportZipJob::class, serialize(new ExportZipJob(7)), 'RuntimeException: export fallo');
        $token = $this->loginAsSuperAdmin();

        $this->getJson("/api/v1/backoffice/trabajos/failed/{$id}", $this->authHeaders($token))
            ->assertStatus(200)
            ->assertJsonPath('data.commandName', ExportZipJob::class)
            ->assertJsonPath('data.exception', 'RuntimeException: export fallo');

        $this->getJson('/api/v1/backoffice/trabajos/failed/999999', $this->authHeaders($token))
            ->assertStatus(404);
    }

    public function test_retry_reintenta_export_y_borra_el_registro(): void
    {
        Queue::fake();

        $user = $this->createSuperAdminUser();
        $export = $this->seedExport($user->id);
        $id = $this->seedFailedJob(ExportZipJob::class, serialize(new ExportZipJob($export->id)));

        $token = $this->loginAs($user);

        $this->postJson("/api/v1/backoffice/trabajos/failed/{$id}/reintentar", [], $this->authHeaders($token))
            ->assertStatus(202)
            ->assertJson(['success' => true, 'export_id' => $export->id]);

        Queue::assertPushed(ExportZipJob::class, fn ($job) => $job->exportId === $export->id);
        $this->assertDatabaseMissing('failed_jobs', ['id' => $id]);
    }

    public function test_retry_rechaza_comandos_distintos_a_exportzipjob(): void
    {
        Queue::fake();

        $id = $this->seedFailedJob('App\\Jobs\\OtroJob', serialize(['algo' => true]));
        $token = $this->loginAsSuperAdmin();

        $this->postJson("/api/v1/backoffice/trabajos/failed/{$id}/reintentar", [], $this->authHeaders($token))
            ->assertStatus(400);

        Queue::assertNothingPushed();
        $this->assertDatabaseHas('failed_jobs', ['id' => $id]);
    }

    public function test_retry_rechaza_si_la_exportacion_no_existe(): void
    {
        Queue::fake();

        $id = $this->seedFailedJob(ExportZipJob::class, serialize(new ExportZipJob(999999)));
        $token = $this->loginAsSuperAdmin();

        $this->postJson("/api/v1/backoffice/trabajos/failed/{$id}/reintentar", [], $this->authHeaders($token))
            ->assertStatus(400)
            ->assertJsonPath('message', 'La exportacion asociada ya no existe');

        Queue::assertNothingPushed();
        $this->assertDatabaseHas('failed_jobs', ['id' => $id]);
    }

    public function test_exportaciones_lista_ultimas_con_capacidad(): void
    {
        $user = $this->createSuperAdminUser();
        $this->seedExport($user->id);

        $token = $this->loginAs($user);

        $response = $this->getJson('/api/v1/backoffice/exportaciones', $this->authHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertCount(1, $response->json('data'));
        $this->assertArrayHasKey('active_exports', $response->json('capacidad'));
        $this->assertArrayHasKey('max_exports', $response->json('capacidad'));
    }

    public function test_exportaciones_devuelve_403_para_admin(): void
    {
        $token = $this->actingAsAdmin();

        $this->getJson('/api/v1/backoffice/exportaciones', $this->authHeaders($token))
            ->assertStatus(403);
    }
}
