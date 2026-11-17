<?php

namespace Tests\Feature\Backoffice;

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\WithApiUser;

class AuditoriaTest extends TestCase
{
    use WithApiUser;

    private function seedAudit(array $atributos = []): AuditLog
    {
        return AuditLog::create(array_merge([
            'user_id' => 1,
            'correo' => 'admin@test.com',
            'accion' => 'login',
            'descripcion' => 'Registro de prueba',
            'created_at' => now(),
        ], $atributos));
    }

    public function test_index_lista_auditoria_para_superadmin(): void
    {
        $this->seedAudit(['accion' => 'login', 'descripcion' => 'Fila A']);
        $this->seedAudit(['accion' => 'errores.purga', 'descripcion' => 'Fila B']);

        $token = $this->loginAsSuperAdmin();

        $response = $this->getJson('/api/v1/backoffice/auditoria', $this->authHeaders($token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $acciones = collect($response->json('data.data'))->pluck('descripcion')->all();
        $this->assertContains('Fila A', $acciones);
        $this->assertContains('Fila B', $acciones);
    }

    public function test_index_filtra_por_accion_y_usuario(): void
    {
        $this->seedAudit(['accion' => 'login', 'user_id' => 10]);
        $this->seedAudit(['accion' => 'login', 'user_id' => 20]);
        $this->seedAudit(['accion' => 'errores.purga', 'user_id' => 10]);

        $token = $this->loginAsSuperAdmin();

        $response = $this->getJson('/api/v1/backoffice/auditoria?accion=login&user_id=10', $this->authHeaders($token));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertSame('login', $response->json('data.data.0.accion'));
        $this->assertSame(10, $response->json('data.data.0.user_id'));
    }

    public function test_index_devuelve_403_para_admin(): void
    {
        $token = $this->actingAsAdmin();

        $this->getJson('/api/v1/backoffice/auditoria', $this->authHeaders($token))
            ->assertStatus(403);
    }

    public function test_show_devuelve_detalle_o_404(): void
    {
        $registro = $this->seedAudit(['datos' => ['clave' => 'valor']]);
        $token = $this->loginAsSuperAdmin();

        $this->getJson("/api/v1/backoffice/auditoria/{$registro->id}", $this->authHeaders($token))
            ->assertStatus(200)
            ->assertJsonPath('data.datos.clave', 'valor');

        $this->getJson('/api/v1/backoffice/auditoria/999999', $this->authHeaders($token))
            ->assertStatus(404);
    }

    public function test_login_registra_auditoria_de_exito(): void
    {
        $user = $this->createAdminUser();

        $this->postJson('/api/v1/login', [
            'correo' => $user->correo,
            'contraseña' => 'password123',
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'accion' => 'login',
            'user_id' => $user->id,
            'correo' => $user->correo,
        ]);
    }

    public function test_login_fallido_registra_auditoria(): void
    {
        $user = $this->createAdminUser();

        $this->postJson('/api/v1/login', [
            'correo' => $user->correo,
            'contraseña' => 'clave-mala',
        ])->assertStatus(403);

        $this->assertDatabaseHas('audit_logs', [
            'accion' => 'login.fallido',
            'user_id' => $user->id,
        ]);
    }

    public function test_purga_errores_registra_auditoria(): void
    {
        DB::table('error_logs')->insert([
            'level' => 'error',
            'message' => 'Para purgar',
            'fingerprint' => sha1('purga-unico'),
            'context' => null,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $this->loginAsSuperAdmin();

        $this->deleteJson('/api/v1/backoffice/errores?level=error', [], $this->authHeaders($token))
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'accion' => 'errores.purga',
            'correo' => 'superadmin@test.com',
        ]);
    }

    public function test_comando_superadmin_assign_registra_auditoria(): void
    {
        $user = $this->createAdminUser();

        $this->artisan('superadmin:assign', ['correo' => $user->correo])
            ->assertExitCode(0);

        $this->assertDatabaseHas('audit_logs', [
            'accion' => 'superadmin.asignado',
            'user_id' => $user->id,
        ]);

        $this->assertSame('SuperAdmin', $user->fresh()->rol);
    }

    public function test_reintento_trabajo_registra_auditoria(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $user = $this->createSuperAdminUser();
        $export = \App\Models\Export::create([
            'user_id' => $user->id,
            'servicio' => 'pacientes',
            'filtros' => ['fechaInicio' => '2026-01-01', 'fechaFin' => '2026-12-31'],
            'estado' => 'error',
        ]);

        $id = DB::table('failed_jobs')->insertGetId([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => \App\Jobs\ExportZipJob::class,
                'data' => [
                    'commandName' => \App\Jobs\ExportZipJob::class,
                    'command' => serialize(new \App\Jobs\ExportZipJob($export->id)),
                ],
            ]),
            'exception' => 'RuntimeException: fallo',
            'failed_at' => now(),
        ]);

        $token = $this->loginAs($user);

        $this->postJson("/api/v1/backoffice/trabajos/failed/{$id}/reintentar", [], $this->authHeaders($token))
            ->assertStatus(202);

        $this->assertDatabaseHas('audit_logs', [
            'accion' => 'trabajos.reintento',
            'user_id' => $user->id,
        ]);
    }
}
