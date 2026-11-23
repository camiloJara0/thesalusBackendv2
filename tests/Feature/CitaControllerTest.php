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
use App\Models\Cita;
use App\Models\Servicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CitaControllerTest extends TestCase
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

    public function test_list_citas(): void
    {
        $response = $this->getJson('/api/v1/citas', $this->authHeaders($this->token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_create_cita(): void
    {
        $response = $this->postJson('/api/v1/citas', [
            'id_paciente' => $this->paciente->id,
            'id_medico' => $this->profesional->id,
            'id_servicio' => $this->servicio->id,
            'motivo' => 'Consulta general',
            'fecha' => now()->addDays(5)->format('Y-m-d'),
            'hora' => '10:00',
        ], $this->authHeaders($this->token));

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_create_cita_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/citas', [], $this->authHeaders($this->token));

        $response->assertStatus(422);
    }

    public function test_update_cita(): void
    {
        $citaId = DB::table('citas')->insertGetId([
            'id_paciente' => $this->paciente->id,
            'id_medico' => $this->profesional->id,
            'id_servicio' => $this->servicio->id,
            'motivo' => 'Consulta inicial',
            'fecha' => now()->addDays(5)->format('Y-m-d'),
            'hora' => '10:00',
            'estado' => 'Inactiva',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->putJson("/api/v1/citas/{$citaId}", [
            'id' => $citaId,
            'id_servicio' => $this->servicio->id,
            'motivo' => 'Motivo actualizado',
            'fecha' => now()->addDays(10)->format('Y-m-d'),
            'hora' => '14:00',
        ], $this->authHeaders($this->token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_show_cita(): void
    {
        $citaId = DB::table('citas')->insertGetId([
            'id_paciente' => $this->paciente->id,
            'id_medico' => $this->profesional->id,
            'id_servicio' => $this->servicio->id,
            'motivo' => 'Consulta',
            'fecha' => now()->addDays(5)->format('Y-m-d'),
            'hora' => '10:00',
            'estado' => 'Inactiva',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/citas/{$citaId}", $this->authHeaders($this->token));

        $response->assertStatus(200);
    }

    public function test_delete_cita(): void
    {
        $citaId = DB::table('citas')->insertGetId([
            'id_paciente' => $this->paciente->id,
            'id_medico' => $this->profesional->id,
            'id_servicio' => $this->servicio->id,
            'motivo' => 'Consulta',
            'fecha' => now()->addDays(5)->format('Y-m-d'),
            'hora' => '10:00',
            'estado' => 'Inactiva',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/citas/{$citaId}", [
            'id' => $citaId,
            'estado' => 'cancelada',
            'motivo_cancelacion' => 'Cancelado por paciente',
        ], $this->authHeaders($this->token));

        $response->assertStatus(200);
    }

    public function test_citas_hoy(): void
    {
        DB::table('citas')->insert([
            'id_paciente' => $this->paciente->id,
            'id_medico' => $this->profesional->id,
            'id_servicio' => $this->servicio->id,
            'motivo' => 'Consulta hoy',
            'fecha' => now()->format('Y-m-d'),
            'hora' => '10:00',
            'estado' => 'Inactiva',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/citasHoy', $this->authHeaders($this->token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_citas_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/citas');

        $response->assertStatus(401);
    }

    public function test_detalle_cita_incluye_auditoria(): void
    {
        $response = $this->postJson('/api/v1/citas', [
            'id_paciente' => $this->paciente->id,
            'id_medico' => $this->profesional->id,
            'id_servicio' => $this->servicio->id,
            'motivo' => 'Consulta general',
            'fecha' => now()->addDays(5)->format('Y-m-d'),
            'hora' => '10:00',
        ], $this->authHeaders($this->token));

        $citaId = $response->json('data.id');

        $detalle = $this->getJson("/api/v1/citasDetalle/{$citaId}", $this->authHeaders($this->token));

        $detalle->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => ['paciente', 'profesional', 'servicio', 'auditorias'],
            ]);

        $this->assertSame('creada', $detalle->json('data.auditorias.0.accion'));
        $this->assertSame('Inactiva', $detalle->json('data.auditorias.0.estado_nuevo'));
    }

    public function test_detalle_cita_no_encontrado(): void
    {
        $response = $this->getJson('/api/v1/citasDetalle/999999', $this->authHeaders($this->token));

        $response->assertStatus(404)
            ->assertJson(['success' => false, 'codigo' => 'no_encontrado']);
    }

    public function test_cancelar_cita_registra_auditoria(): void
    {
        $citaId = DB::table('citas')->insertGetId([
            'id_paciente' => $this->paciente->id,
            'id_medico' => $this->profesional->id,
            'id_servicio' => $this->servicio->id,
            'motivo' => 'Consulta',
            'fecha' => now()->addDays(5)->format('Y-m-d'),
            'hora' => '10:00',
            'estado' => 'Inactiva',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson("/api/v1/citas/{$citaId}", [
            'id' => $citaId,
            'estado' => 'cancelada',
            'motivo_cancelacion' => 'Cancelado por paciente',
        ], $this->authHeaders($this->token));

        $detalle = $this->getJson("/api/v1/citasDetalle/{$citaId}", $this->authHeaders($this->token));

        $auditorias = $detalle->json('data.auditorias');
        $this->assertNotEmpty($auditorias);
        $this->assertSame('cancelada', end($auditorias)['accion']);
        $this->assertSame('Cancelado por paciente', end($auditorias)['detalle']);
        $this->assertSame('Inactiva', end($auditorias)['estado_anterior']);
    }

    public function test_formato_error_estandarizado_en_validacion(): void
    {
        $response = $this->postJson('/api/v1/citas', [], $this->authHeaders($this->token));

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'codigo' => 'validacion']);
    }
}
