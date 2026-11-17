<?php

namespace Tests\Feature\Backoffice;

use App\Models\InformacionUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\WithApiUser;

class UsuariosTest extends TestCase
{
    use WithApiUser;

    private function crearUsuario(string $correo, string $rol, string $documento): User
    {
        $empresa = $this->ensureEmpresaId1();

        $info = InformacionUser::create([
            'name' => 'Usuario ' . $correo,
            'No_document' => $documento,
            'type_doc' => 'CC',
            'celular' => '300999' . substr($documento, -4),
            'nacimiento' => '1990-01-01',
            'direccion' => 'Calle 1 #1-1',
            'municipio' => 'Bogota',
            'departamento' => 'Bogota',
            'barrio' => 'Centro',
            'zona' => 'Urbana',
        ]);

        return User::create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
            'correo' => $correo,
            'contraseña' => Hash::make('password123'),
            'rol' => $rol,
            'estado' => 1,
        ]);
    }

    public function test_index_lista_usuarios_con_tokens_activos(): void
    {
        $user = $this->crearUsuario('listado@test.com', 'Profesional', '2000000001');
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'activa',
            'token' => hash('sha256', Str::random(40)),
            'abilities' => '["*"]',
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $this->loginAsSuperAdmin();

        $response = $this->getJson('/api/v1/backoffice/usuarios', $this->authHeaders($token));

        $response->assertStatus(200)->assertJson(['success' => true]);

        $fila = collect($response->json('data.data'))->firstWhere('correo', 'listado@test.com');
        $this->assertNotNull($fila);
        $this->assertSame('Profesional', $fila['rol']);
        $this->assertSame(1, $fila['tokens_activos']);
    }

    public function test_show_devuelve_permisos_efectivos_y_tokens(): void
    {
        $admin = $this->crearUsuario('detalledemo@test.com', 'Admin', '2000000002');
        $token = $this->loginAsSuperAdmin();

        $response = $this->getJson("/api/v1/backoffice/usuarios/{$admin->id}", $this->authHeaders($token));

        $response->assertStatus(200);

        $permisos = $response->json('data.permisos_efectivos');
        $this->assertContains('Backoffice_view', $permisos);
        $this->assertContains('Citas_view', $permisos);
        $this->assertIsArray($response->json('data.tokens_recientes'));
    }

    public function test_purgar_tokens_elimina_solo_los_del_usuario_y_audita(): void
    {
        $user = $this->crearUsuario('purgar@test.com', 'Profesional', '2000000003');

        DB::table('personal_access_tokens')->insert([
            ['tokenable_type' => User::class, 'tokenable_id' => $user->id, 'name' => 'a', 'token' => hash('sha256', Str::random(40)), 'abilities' => '["*"]', 'expires_at' => now()->addHour(), 'created_at' => now(), 'updated_at' => now()],
            ['tokenable_type' => User::class, 'tokenable_id' => $user->id, 'name' => 'b', 'token' => hash('sha256', Str::random(40)), 'abilities' => '["*"]', 'expires_at' => now()->addHour(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $token = $this->loginAsSuperAdmin();

        $this->postJson("/api/v1/backoffice/usuarios/{$user->id}/tokens/purgar", [], $this->authHeaders($token))
            ->assertStatus(200)
            ->assertJson(['success' => true, 'tokens_eliminados' => 2]);

        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->count());
        $this->assertDatabaseHas('audit_logs', ['accion' => 'usuarios.tokens_purgados']);
    }

    public function test_show_devuelve_404_si_no_existe(): void
    {
        $token = $this->loginAsSuperAdmin();

        $this->getJson('/api/v1/backoffice/usuarios/999999', $this->authHeaders($token))
            ->assertStatus(404);
    }

    public function test_backoffice_usuarios_devuelve_403_para_admin(): void
    {
        $token = $this->actingAsAdmin();

        $this->getJson('/api/v1/backoffice/usuarios', $this->authHeaders($token))
            ->assertStatus(403);
    }
}
