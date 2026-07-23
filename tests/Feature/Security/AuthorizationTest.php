<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Tests\Traits\WithApiUser;
use App\Models\User;
use App\Models\InformacionUser;
use App\Models\Empresa;
use App\Models\Eps;
use App\Models\Paciente;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase, WithApiUser;

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $routes = [
            'GET /api/v1/users',
            'GET /api/v1/pacientes',
            'GET /api/v1/profesionals',
            'GET /api/v1/citas',
            'GET /api/v1/eps',
            'GET /api/v1/historiasClinicas',
        ];

        foreach ($routes as $route) {
            [$method, $uri] = explode(' ', $route);
            $response = $this->json($method, $uri);
            $response->assertStatus(401, "Route {$route} should require authentication");
        }
    }

    public function test_admin_user_can_access_all_routes(): void
    {
        $token = $this->actingAsAdmin();

        $routes = [
            ['GET', '/api/v1/users'],
            ['GET', '/api/v1/pacientes'],
            ['GET', '/api/v1/profesionals'],
            ['GET', '/api/v1/citas'],
            ['GET', '/api/v1/eps'],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->json($method, $uri);
            $response->assertStatus(200, "Admin should access {$method} {$uri}");
        }
    }

    public function test_login_returns_correct_role(): void
    {
        $empresa = Empresa::factory()->create();
        $info = InformacionUser::factory()->create();
        $user = User::factory()->admin()->create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
            'correo' => 'role@test.com',
            'contraseña' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'correo' => 'role@test.com',
            'contraseña' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.rol', 'Admin');
    }

    public function test_profesional_login_returns_permissions(): void
    {
        $empresa = Empresa::factory()->create();
        $info = InformacionUser::factory()->create();
        $user = User::factory()->profesional()->create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
            'correo' => 'permisos@test.com',
            'contraseña' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'correo' => 'permisos@test.com',
            'contraseña' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['permisos'],
                'permisosTemporales',
            ]);
    }

    public function test_disabled_user_cannot_login(): void
    {
        $empresa = Empresa::factory()->create();
        $info = InformacionUser::factory()->create();
        $user = User::factory()->disabled()->create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
            'correo' => 'disabled@test.com',
            'contraseña' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'correo' => 'disabled@test.com',
            'contraseña' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson(['type' => 'USER_DELETE']);
    }
}
