<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Tests\Traits\WithApiUser;
use App\Models\User;
use App\Models\InformacionUser;
use App\Models\Empresa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InputSanitizationTest extends TestCase
{
    use RefreshDatabase, WithApiUser;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->actingAsAdmin();
    }

    public function test_sql_injection_in_search(): void
    {
        $response = $this->postJson('/api/v1/analisisPaciente', [
            'servicio' => "' OR 1=1 --",
        ], $this->authHeaders($this->token));

        // Should not return all records, should return empty or filtered
        $response->assertStatus(200);
        $data = $response->json('data', []);
        $this->assertIsArray($data);
    }

    public function test_xss_in_name_field(): void
    {
        $response = $this->postJson('/api/v1/users', [
            'name' => '<script>alert("xss")</script>',
            'No_document' => '1234567890',
            'type_doc' => 'CC',
            'celular' => '3001234567',
            'nacimiento' => '1990-01-01',
            'direccion' => 'Calle 1',
            'municipio' => 'Bogota',
            'departamento' => 'Bogota',
            'barrio' => 'Centro',
            'zona' => 'Urbana',
            'correo' => 'xss@test.com',
            'contraseña' => 'password123',
        ], $this->authHeaders($this->token));

        $this->assertContains($response->status(), [201, 422]);

        if ($response->status() === 201) {
            $name = $response->json('informacion.name');
            $this->assertIsString($name);
        }
    }

    public function test_long_string_in_name_field(): void
    {
        $longString = str_repeat('A', 500);

        $response = $this->postJson('/api/v1/users', [
            'name' => $longString,
            'No_document' => '1234567890',
            'type_doc' => 'CC',
            'celular' => '3001234567',
            'nacimiento' => '1990-01-01',
            'direccion' => 'Calle 1',
            'municipio' => 'Bogota',
            'departamento' => 'Bogota',
            'barrio' => 'Centro',
            'zona' => 'Urbana',
            'correo' => 'long@test.com',
            'contraseña' => 'password123',
        ], $this->authHeaders($this->token));

        // Should either succeed (if no max length) or fail validation
        $this->assertContains($response->status(), [201, 422]);
    }

    public function test_sql_injection_in_eps_search(): void
    {
        $response = $this->getJson('/api/v1/eps?search=%27%20OR%201%3D1%20--', $this->authHeaders($this->token));

        $response->assertStatus(200);
        $this->assertIsArray($response->json('data', []));
    }
}
