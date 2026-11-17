<?php

namespace Tests\Feature\Backoffice;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\WithApiUser;

class BackofficeAccessTest extends TestCase
{
    use WithApiUser;

    public function test_migracion_crea_11_secciones_backoffice(): void
    {
        $secciones = DB::table('secciones')
            ->where('nombre', 'like', 'Backoffice_%')
            ->pluck('nombre')
            ->sort()
            ->values()
            ->all();

        $this->assertCount(11, $secciones);
        $this->assertContains('Backoffice_view', $secciones);
        $this->assertContains('Backoffice_errores', $secciones);
        $this->assertContains('Backoffice_respaldos', $secciones);
    }

    public function test_ping_responde_200_para_superadmin(): void
    {
        $user = $this->createSuperAdminUser();
        $token = $this->loginAs($user);

        $response = $this->getJson('/api/v1/backoffice/ping', $this->authHeaders($token));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'rol' => 'SuperAdmin',
            ]);

        $permisos = $response->json('permisos_backoffice');
        $this->assertContains('Backoffice_view', $permisos);
        $this->assertContains('Backoffice_errores', $permisos);
        $this->assertCount(11, $permisos);
    }

    public function test_ping_devuelve_403_para_admin(): void
    {
        $token = $this->actingAsAdmin();

        $this->getJson('/api/v1/backoffice/ping', $this->authHeaders($token))
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_ping_devuelve_403_para_profesional(): void
    {
        $data = $this->createProfesionalUser();
        $token = $this->loginAs($data['user']);

        $this->getJson('/api/v1/backoffice/ping', $this->authHeaders($token))
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_ping_devuelve_401_sin_token(): void
    {
        $this->getJson('/api/v1/backoffice/ping')
            ->assertStatus(401);
    }

    public function test_login_superadmin_recibe_todas_las_secciones_backoffice(): void
    {
        $user = $this->createSuperAdminUser();

        $response = $this->postJson('/api/v1/login', [
            'correo' => 'superadmin@test.com',
            'contraseña' => 'password123',
        ]);

        $response->assertStatus(200);
        $permisos = $response->json('user.permisos');
        $this->assertContains('Backoffice_view', $permisos);
        $this->assertContains('Backoffice_metricas', $permisos);
    }

    public function test_comando_superadmin_assign_asigna_rol(): void
    {
        $user = $this->createAdminUser();

        $this->artisan('superadmin:assign', ['correo' => 'admin@test.com'])
            ->assertExitCode(0);

        $this->assertSame('SuperAdmin', $user->fresh()->rol);
    }

    public function test_comando_superadmin_assign_falla_si_no_existe(): void
    {
        $this->artisan('superadmin:assign', ['correo' => 'noexiste@test.com'])
            ->assertExitCode(1);
    }

    public function test_solicitar_permiso_backoffice_es_rechazado(): void
    {
        $data = $this->createProfesionalUser();
        $token = $this->loginAs($data['user']);

        $idSeccion = DB::table('secciones')->where('nombre', 'Backoffice_errores')->value('id');

        $response = $this->postJson('/api/v1/solicitarPermiso', [
            'id_profesional' => $data['profesional']->id,
            'id_seccion' => $idSeccion,
        ], $this->authHeaders($token));

        $response->assertStatus(403)
            ->assertJson(['success' => false]);

        $this->assertSame(0, DB::table('solicitud_permisos')->count());
    }

    public function test_aprobar_permiso_backoffice_es_rechazado(): void
    {
        $data = $this->createProfesionalUser();
        $idSeccion = DB::table('secciones')->where('nombre', 'Backoffice_view')->value('id');

        DB::table('solicitud_permisos')->insert([
            'id_profesional' => $data['profesional']->id,
            'id_seccion' => $idSeccion,
            'token_aprobacion' => 'token-backoffice-test',
            'estado' => 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminToken = $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/aprobarPermiso', [
            'token' => 'token-backoffice-test',
        ], $this->authHeaders($adminToken));

        $response->assertStatus(403)
            ->assertJson(['success' => false]);

        $this->assertSame(
            'pendiente',
            DB::table('solicitud_permisos')->where('token_aprobacion', 'token-backoffice-test')->value('estado')
        );
        $this->assertSame(
            0,
            DB::table('profesional_has_permisos')->where('id_seccion', $idSeccion)->count()
        );
    }
}
