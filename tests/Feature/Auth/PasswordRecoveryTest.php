<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Tests\Traits\WithApiUser;
use App\Models\User;
use App\Models\InformacionUser;
use App\Models\Empresa;
use App\Models\CodigoVerificacion;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase, WithApiUser;

    private function setupUser(): User
    {
        $empresa = Empresa::factory()->create();
        $info = InformacionUser::factory()->create();
        return User::factory()->create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
            'correo' => 'recovery@test.com',
            'contraseña' => Hash::make('oldpassword'),
        ]);
    }

    public function test_request_recovery_with_valid_email(): void
    {
        $user = $this->setupUser();

        $response = $this->postJson('/api/v1/recuperarContraseña', [
            'correo' => 'recovery@test.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_request_recovery_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/recuperarContraseña', [
            'correo' => 'noexiste@test.com',
        ]);

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_change_password_with_valid_code(): void
    {
        $user = $this->setupUser();

        $codigo = CodigoVerificacion::factory()->create([
            'correo' => 'recovery@test.com',
            'codigo' => '123456',
            'expira_en' => now()->addMinutes(10),
            'usado' => false,
        ]);

        $response = $this->postJson('/api/v1/cambiarContraseña', [
            'correo' => 'recovery@test.com',
            'codigo' => '123456',
            'contraseña' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->contraseña));
    }

    public function test_change_password_with_invalid_code(): void
    {
        $user = $this->setupUser();

        $response = $this->postJson('/api/v1/cambiarContraseña', [
            'correo' => 'recovery@test.com',
            'codigo' => '000000',
            'contraseña' => 'newpassword123',
        ]);

        $this->assertContains($response->status(), [401, 422]);
    }

    public function test_change_password_with_expired_code(): void
    {
        $user = $this->setupUser();

        CodigoVerificacion::factory()->expired()->create([
            'correo' => 'recovery@test.com',
            'codigo' => '999999',
            'usado' => false,
        ]);

        $response = $this->postJson('/api/v1/cambiarContraseña', [
            'correo' => 'recovery@test.com',
            'codigo' => '999999',
            'contraseña' => 'newpassword123',
        ]);

        $this->assertContains($response->status(), [401, 422]);
    }

    public function test_recovery_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/recuperarContraseña', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['correo']);
    }

    public function test_change_password_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/cambiarContraseña', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['correo', 'codigo', 'contraseña']);
    }
}
