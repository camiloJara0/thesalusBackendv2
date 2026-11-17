<?php

namespace Tests\Feature\Backoffice;

use Tests\TestCase;
use Tests\Traits\WithApiUser;

class SaludTest extends TestCase
{
    use WithApiUser;

    public function test_salud_devuelve_estado_completo_para_superadmin(): void
    {
        $token = $this->loginAsSuperAdmin();

        $response = $this->getJson('/api/v1/backoffice/salud', $this->authHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('base_datos.conectada', true)
            ->assertJsonPath('cola.conexion', config('queue.default'));

        $this->assertArrayHasKey('php', $response->json());
        $this->assertArrayHasKey('laravel', $response->json());
        $this->assertArrayHasKey('disco', $response->json());
        $this->assertArrayHasKey('log', $response->json());
        $this->assertArrayHasKey('tablas', $response->json());
        $this->assertArrayHasKey('users', $response->json('tablas'));
    }

    public function test_salud_no_expone_secretos(): void
    {
        $token = $this->loginAsSuperAdmin();

        $contenido = $this->getJson('/api/v1/backoffice/salud', $this->authHeaders($token))->getContent();

        $this->assertStringNotContainsString('APP_KEY', $contenido);
        $this->assertStringNotContainsString('base64:', $contenido);
        $this->assertStringNotContainsString('password', $contenido);
        $this->assertStringNotContainsString('contraseña', $contenido);
    }

    public function test_salud_devuelve_403_para_admin(): void
    {
        $token = $this->actingAsAdmin();

        $this->getJson('/api/v1/backoffice/salud', $this->authHeaders($token))
            ->assertStatus(403);
    }
}
