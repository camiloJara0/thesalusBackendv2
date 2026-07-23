<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithApiUser;
use App\Models\User;
use App\Models\InformacionUser;
use App\Models\Empresa;
use App\Models\Eps;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Profesion;
use App\Models\Servicio;
use App\Models\Historia_Clinica;
use App\Models\Analisis;
use App\Models\Diagnostico;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HistoriaClinicaControllerTest extends TestCase
{
    use RefreshDatabase, WithApiUser;

    private string $token;
    private Paciente $paciente;
    private Profesional $profesional;
    private Servicio $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->actingAsAdmin();

        $eps = Eps::factory()->create();
        $infoP = InformacionUser::factory()->create();
        $this->paciente = Paciente::factory()->create([
            'id_eps' => $eps->id,
            'id_infoUsuario' => $infoP->id,
        ]);

        $profesion = Profesion::factory()->create();
        $infoM = InformacionUser::factory()->create();
        $this->profesional = Profesional::factory()->create([
            'id_profesion' => $profesion->id,
            'id_infoUsuario' => $infoM->id,
        ]);

        $this->servicio = Servicio::factory()->create();
    }

    private function createHistoriaClinica(): int
    {
        return DB::table('historia__clinicas')->insertGetId([
            'id_paciente' => $this->paciente->id,
            'fecha_historia' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_list_historias_clinicas(): void
    {
        $this->createHistoriaClinica();

        $response = $this->getJson('/api/v1/historiasClinicas', $this->authHeaders($this->token));

        $response->assertStatus(200);
    }

    public function test_create_historia_clinica(): void
    {
        $response = $this->postJson('/api/v1/historiasClinicas', [
            'historia' => [
                'id_paciente' => $this->paciente->id,
                'fecha_historia' => now()->format('Y-m-d'),
            ],
            'motivo' => 'Dolor de cabeza',
            'observacion' => 'Paciente presenta cefalea',
            'tratamiento' => 'Analgesicos',
            'analisis' => 'Cefalea tensional',
            'tipoAnalisis' => 'Evolucion',
            'id_medico' => $this->profesional->id,
            'id_servicio' => $this->servicio->id,
            'Diagnosticos' => [],
            'Antecedentes' => [],
        ], $this->authHeaders($this->token));

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_show_historia_clinica(): void
    {
        $historiaId = $this->createHistoriaClinica();

        $response = $this->getJson("/api/v1/historiasClinicas/{$historiaId}", $this->authHeaders($this->token));

        $response->assertStatus(200);
    }

    public function test_create_analisis(): void
    {
        $historiaId = $this->createHistoriaClinica();

        $response = $this->postJson('/api/v1/analisis', [
            'id_historia' => $historiaId,
            'id_medico' => $this->profesional->id,
            'id_servicio' => $this->servicio->id,
            'motivo' => 'Dolor de cabeza',
            'analisis' => 'Cefalea tensional',
            'tipoAnalisis' => 'Evolucion',
        ], $this->authHeaders($this->token));

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Análisis registrado exitosamente.']);
    }

    public function test_analisis_with_diagnosticos(): void
    {
        $historiaId = $this->createHistoriaClinica();

        $analisisId = DB::table('analises')->insertGetId([
            'id_historia' => $historiaId,
            'id_medico' => $this->profesional->id,
            'id_servicio' => $this->servicio->id,
            'motivo' => 'Dolor de cabeza',
            'analisis' => 'Cefalea tensional',
            'tipoAnalisis' => 'Evolucion',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('diagnosticos')->insert([
            ['id_analisis' => $analisisId, 'descripcion' => 'DG1', 'codigo' => 'D01', 'created_at' => now(), 'updated_at' => now()],
            ['id_analisis' => $analisisId, 'descripcion' => 'DG2', 'codigo' => 'D02', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->getJson("/api/v1/analisis/{$analisisId}", $this->authHeaders($this->token));

        $response->assertStatus(200);
    }

    public function test_historia_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/historiasClinicas');

        $response->assertStatus(401);
    }
}
