<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithApiUser;
use App\Models\User;
use App\Models\InformacionUser;
use App\Models\Empresa;
use App\Models\Profesional;
use App\Models\Profesion;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfesionalControllerTest extends TestCase
{
    use RefreshDatabase, WithApiUser;

    private string $token;
    private int $adminInfoUsuarioId;

    protected function setUp(): void
    {
        parent::setUp();
        $user = $this->createAdminUser();
        $this->adminInfoUsuarioId = $user->id_infoUsuario;
        $this->token = $this->loginAs($user);
    }

    private function profesionalData(Profesion $profesion, ?string $correo = null, ?string $cedula = null): array
    {
        return [
            'name' => 'Dr. Test',
            'No_document' => $cedula ?: '20' . mt_rand(1000000, 9999999),
            'type_doc' => 'CC',
            'celular' => '3001112233',
            'nacimiento' => '1985-03-10',
            'direccion' => 'Calle 5 #3-10',
            'municipio' => 'Bogota',
            'departamento' => 'Bogota',
            'barrio' => 'Centro',
            'zona' => 'Urbana',
            'correo' => $correo ?: 'prof_' . mt_rand(10000, 99999) . '@test.com',
            'contraseña' => 'password123',
            'id_profesion' => $profesion->id,
            'zona_laboral' => 'Urbana',
            'departamento_laboral' => 'Bogota',
            'municipio_laboral' => 'Bogota',
            'id_correoCreador' => (string) $this->adminInfoUsuarioId,
        ];
    }

    public function test_list_active_profesionales(): void
    {
        $profesion = Profesion::factory()->create();
        $info = InformacionUser::factory()->create();
        Profesional::factory()->create([
            'id_profesion' => $profesion->id,
            'id_infoUsuario' => $info->id,
            'estado' => 1,
        ]);

        $response = $this->getJson('/api/v1/profesionals', $this->authHeaders($this->token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_create_profesional(): void
    {
        $profesion = Profesion::factory()->create();

        $response = $this->postJson('/api/v1/profesionals', $this->profesionalData($profesion), $this->authHeaders($this->token));

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_create_profesional_duplicate_correo(): void
    {
        $profesion = Profesion::factory()->create();
        $empresa = Empresa::factory()->create();
        $info = InformacionUser::factory()->create();
        User::factory()->create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
            'correo' => 'exist@test.com',
        ]);

        $response = $this->postJson('/api/v1/profesionals', $this->profesionalData($profesion, 'exist@test.com'), $this->authHeaders($this->token));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['correo']);
    }

    public function test_create_profesional_duplicate_cedula(): void
    {
        $profesion = Profesion::factory()->create();
        InformacionUser::factory()->create(['No_document' => '1111111111']);

        $response = $this->postJson('/api/v1/profesionals', $this->profesionalData($profesion, null, '1111111111'), $this->authHeaders($this->token));

        $response->assertStatus(409);
    }

    public function test_update_profesional(): void
    {
        $profesion = Profesion::factory()->create();
        $info = InformacionUser::factory()->create();
        $profesional = Profesional::factory()->create([
            'id_profesion' => $profesion->id,
            'id_infoUsuario' => $info->id,
        ]);
        User::factory()->create([
            'id_infoUsuario' => $info->id,
            'correo' => 'prof@test.com',
        ]);

        $response = $this->putJson("/api/v1/profesionals/{$profesional->id}", [
            'id' => $profesional->id,
            'id_infoUsuario' => $info->id,
            'name' => $info->name,
            'No_document' => $info->No_document,
            'type_doc' => $info->type_doc,
            'celular' => $info->celular,
            'nacimiento' => $info->nacimiento,
            'direccion' => $info->direccion,
            'municipio' => $info->municipio,
            'departamento' => $info->departamento,
            'barrio' => $info->barrio ?? 'Centro',
            'zona' => $info->zona ?? 'Urbana',
            'correo' => 'prof@test.com',
            'id_profesion' => $profesion->id,
            'zona_laboral' => 'Rural',
            'departamento_laboral' => 'Antioquia',
            'municipio_laboral' => 'Medellin',
            'estado' => 1,
        ], $this->authHeaders($this->token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_disable_profesional(): void
    {
        $profesion = Profesion::factory()->create();
        $info = InformacionUser::factory()->create();
        $profesional = Profesional::factory()->create([
            'id_profesion' => $profesion->id,
            'id_infoUsuario' => $info->id,
        ]);

        $response = $this->deleteJson("/api/v1/profesionals/{$profesional->id}", [], $this->authHeaders($this->token));

        $response->assertStatus(200);
    }

    public function test_profesionales_inactivos(): void
    {
        $profesion = Profesion::factory()->create();
        $info = InformacionUser::factory()->create();
        Profesional::factory()->create([
            'id_profesion' => $profesion->id,
            'id_infoUsuario' => $info->id,
            'estado' => 0,
        ]);

        $response = $this->getJson('/api/v1/profesionalesInactivos', $this->authHeaders($this->token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_profesional_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/profesionals');

        $response->assertStatus(401);
    }

    public function test_create_profesional_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/profesionals', [], $this->authHeaders($this->token));

        $response->assertStatus(422);
    }
}
