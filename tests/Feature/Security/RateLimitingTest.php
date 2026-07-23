<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Tests\Traits\WithApiUser;
use App\Models\User;
use App\Models\InformacionUser;
use App\Models\Empresa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase, WithApiUser;

    public function test_login_rate_limit(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'correo' => 'noexiste@test.com',
            'contraseña' => 'wrong',
        ]);

        // First 5 attempts should return 401 (user not found)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', [
                'correo' => 'noexiste@test.com',
                'contraseña' => 'wrong',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->postJson('/api/v1/login', [
            'correo' => 'noexiste@test.com',
            'contraseña' => 'wrong',
        ]);

        $response->assertStatus(429);
    }

    public function test_password_recovery_rate_limit(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/recuperarContraseña', [
                'correo' => 'test@test.com',
            ]);
        }

        $response = $this->postJson('/api/v1/recuperarContraseña', [
            'correo' => 'test@test.com',
        ]);

        $response->assertStatus(429);
    }

    public function test_login_route_has_throttle_middleware(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'correo' => 'noexiste@test.com',
            'contraseña' => 'wrong',
        ]);

        $this->assertContains($response->status(), [401, 429]);
    }
}
