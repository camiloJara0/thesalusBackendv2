<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithApiUser;
use App\Models\User;
use App\Models\InformacionUser;
use App\Models\Empresa;
use App\Models\Eps;
use App\Models\Paciente;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PacienteControllerTest extends TestCase
{
    use RefreshDatabase, WithApiUser;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->actingAsAdmin();
    }

    private function pacienteData(Eps $eps, ?string $cedula = null): array
    {
        return [
            'info_usuario' => [
                'name' => 'Paciente Test',
                'No_document' => $cedula ?: '10' . mt_rand(1000000, 9999999),
                'type_doc' => 'CC',
                'celular' => '3001112233',
                'telefono' => null,
                'nacimiento' => '1995-05-15',
                'direccion' => 'Calle 10 #5-20',
                'municipio' => 'Bogota',
                'departamento' => 'Bogota',
                'barrio' => 'Centro',
                'zona' => 'Urbana',
            ],
            'id_eps' => $eps->id,
            'genero' => 'M',
            'sexo' => 'M',
            'regimen' => 'Contributivo',
            'vulnerabilidad' => 'Ninguna',
        ];
    }

    public function test_list_active_pacientes(): void
    {
        $eps = Eps::factory()->create();
        $info = InformacionUser::factory()->create();
        Paciente::factory()->create([
            'id_eps' => $eps->id,
            'id_infoUsuario' => $info->id,
            'estado' => 1,
        ]);

        $response = $this->getJson('/api/v1/pacientes', $this->authHeaders($this->token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_create_paciente(): void
    {
        $eps = Eps::factory()->create();

        $response = $this->postJson('/api/v1/pacientes', $this->pacienteData($eps), $this->authHeaders($this->token));

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_create_paciente_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/pacientes', [], $this->authHeaders($this->token));

        $response->assertStatus(422);
    }

    public function test_create_paciente_duplicate_cedula(): void
    {
        $eps = Eps::factory()->create();
        $info = InformacionUser::factory()->create(['No_document' => '1111111111']);
        Paciente::factory()->create(['id_eps' => $eps->id, 'id_infoUsuario' => $info->id]);

        $response = $this->postJson('/api/v1/pacientes', $this->pacienteData($eps, '1111111111'), $this->authHeaders($this->token));

        $response->assertStatus(409);
    }

    public function test_update_paciente(): void
    {
        $eps = Eps::factory()->create();
        $info = InformacionUser::factory()->create();
        $paciente = Paciente::factory()->create([
            'id_eps' => $eps->id,
            'id_infoUsuario' => $info->id,
        ]);

        $response = $this->putJson("/api/v1/pacientes/{$paciente->id}", [
            'id' => $paciente->id,
            'info_usuario' => [
                'name' => $info->name,
                'No_document' => (int) $info->No_document,
                'type_doc' => $info->type_doc,
                'celular' => $info->celular,
                'telefono' => null,
                'nacimiento' => $info->nacimiento,
                'direccion' => $info->direccion,
                'municipio' => $info->municipio,
                'departamento' => $info->departamento,
                'barrio' => $info->barrio ?? 'Centro',
                'zona' => $info->zona ?? 'Urbana',
            ],
            'id_infoUsuario' => $info->id,
            'id_eps' => $eps->id,
            'genero' => 'F',
            'sexo' => 'F',
            'regimen' => 'Subsidiado',
            'vulnerabilidad' => 'Alta',
            'estado' => 1,
        ], $this->authHeaders($this->token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_show_paciente(): void
    {
        $eps = Eps::factory()->create();
        $info = InformacionUser::factory()->create();
        $paciente = Paciente::factory()->create([
            'id_eps' => $eps->id,
            'id_infoUsuario' => $info->id,
        ]);

        $response = $this->getJson("/api/v1/pacientes/{$paciente->id}", $this->authHeaders($this->token));

        $response->assertStatus(200);
    }

    public function test_pacientes_inactivos(): void
    {
        $eps = Eps::factory()->create();
        $info = InformacionUser::factory()->create();
        Paciente::factory()->create([
            'id_eps' => $eps->id,
            'id_infoUsuario' => $info->id,
            'estado' => 0,
        ]);

        $response = $this->getJson('/api/v1/pacientesInactivos', $this->authHeaders($this->token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_delete_paciente(): void
    {
        $eps = Eps::factory()->create();
        $info = InformacionUser::factory()->create();
        $paciente = Paciente::factory()->create([
            'id_eps' => $eps->id,
            'id_infoUsuario' => $info->id,
        ]);

        $response = $this->deleteJson("/api/v1/pacientes/{$paciente->id}", [], $this->authHeaders($this->token));

        $response->assertStatus(200);
    }

    public function test_paciente_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/pacientes');

        $response->assertStatus(401);
    }
}
