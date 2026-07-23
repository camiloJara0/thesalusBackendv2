<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithApiUser;
use App\Models\Eps;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EpsControllerTest extends TestCase
{
    use RefreshDatabase, WithApiUser;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->actingAsAdmin();
    }

    public function test_list_eps(): void
    {
        Eps::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/eps', $this->authHeaders($this->token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_create_eps(): void
    {
        $response = $this->postJson('/api/v1/eps', [
            'nombre' => 'Nueva EPS',
            'codigo' => 'EPS001',
            'nit' => '800123456',
        ], $this->authHeaders($this->token));

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('eps', ['nombre' => 'Nueva EPS']);
    }

    public function test_create_eps_duplicate_name(): void
    {
        Eps::factory()->create(['nombre' => 'Duplicada']);

        $response = $this->postJson('/api/v1/eps', [
            'nombre' => 'Duplicada',
            'codigo' => 'EPS002',
            'nit' => '800999999',
        ], $this->authHeaders($this->token));

        $this->assertContains($response->status(), [409, 422]);
    }

    public function test_create_eps_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/eps', [], $this->authHeaders($this->token));

        $this->assertContains($response->status(), [409, 422]);
    }

    public function test_show_eps(): void
    {
        $eps = Eps::factory()->create();

        $response = $this->getJson("/api/v1/eps/{$eps->id}", $this->authHeaders($this->token));

        $response->assertStatus(200);
        $this->assertDatabaseHas('eps', ['id' => $eps->id, 'nombre' => $eps->nombre]);
    }

    public function test_update_eps(): void
    {
        $eps = Eps::factory()->create();

        $response = $this->putJson("/api/v1/eps/{$eps->id}", [
            'id' => $eps->id,
            'nombre' => 'EPS Actualizada',
            'codigo' => 'EPS003',
            'nit' => '800111111',
        ], $this->authHeaders($this->token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_delete_eps(): void
    {
        $eps = Eps::factory()->create();

        $response = $this->deleteJson("/api/v1/eps/{$eps->id}", [], $this->authHeaders($this->token));

        $response->assertStatus(200);
    }

    public function test_eps_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/eps');

        $response->assertStatus(401);
    }
}
