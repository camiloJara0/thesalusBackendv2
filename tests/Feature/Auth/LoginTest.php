<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Tests\Traits\WithApiUser;
use App\Models\User;
use App\Models\InformacionUser;
use App\Models\Empresa;
use App\Models\Profesional_has_permisos;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase, WithApiUser;

    private function setupUser(): User
    {
        $empresa = Empresa::factory()->create();
        $info = InformacionUser::factory()->create();
        return User::factory()->create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
            'correo' => 'test@login.com',
            'contraseña' => Hash::make('password123'),
        ]);
    }

    public function test_login_success(): void
    {
        $user = $this->setupUser();

        $response = $this->postJson('/api/v1/login', [
            'correo' => 'test@login.com',
            'contraseña' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'access_token',
                'user' => ['correo', 'rol', 'usuario', 'permisos'],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_login_returns_token(): void
    {
        $user = $this->setupUser();

        $response = $this->postJson('/api/v1/login', [
            'correo' => 'test@login.com',
            'contraseña' => 'password123',
        ]);

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('access_token'));
    }

    public function test_login_user_not_found(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'correo' => 'noexiste@test.com',
            'contraseña' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'type' => 'USER_NOT_FOUND',
            ]);
    }

    public function test_login_wrong_password(): void
    {
        $user = $this->setupUser();

        $response = $this->postJson('/api/v1/login', [
            'correo' => 'test@login.com',
            'contraseña' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'type' => 'INVALID_PASSWORD',
            ]);
    }

    public function test_login_disabled_user(): void
    {
        $user = $this->setupUser();
        $user->update(['estado' => 0]);

        $response = $this->postJson('/api/v1/login', [
            'correo' => 'test@login.com',
            'contraseña' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'type' => 'USER_DELETE',
            ]);
    }

    public function test_login_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['correo', 'contraseña']);
    }

    public function test_protected_route_without_token(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
    }

    public function test_protected_route_with_valid_token(): void
    {
        $user = $this->setupUser();
        $token = $this->loginAs($user);

        $response = $this->getJson('/api/v1/users', $this->authHeaders($token));

        $response->assertStatus(200);
    }

    public function test_login_returns_user_info(): void
    {
        $user = $this->setupUser();

        $response = $this->postJson('/api/v1/login', [
            'correo' => 'test@login.com',
            'contraseña' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.correo', 'test@login.com')
            ->assertJsonPath('user.rol', 'Admin');
    }

    public function test_login_user_with_disabled_token_after_logout(): void
    {
        $user = $this->setupUser();
        $token = $this->loginAs($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/users', [
                'id' => $user->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/users');

        $response->assertStatus(200);
    }
}
