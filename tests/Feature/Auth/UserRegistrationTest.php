<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Tests\Traits\WithApiUser;
use App\Models\User;
use App\Models\InformacionUser;
use App\Models\Empresa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase, WithApiUser;

    private array $validData = [
        'name' => 'Test Admin',
        'No_document' => '1234567890',
        'type_doc' => 'CC',
        'celular' => '3001234567',
        'nacimiento' => '1990-01-01',
        'direccion' => 'Calle 1 #1-1',
        'municipio' => 'Bogota',
        'departamento' => 'Bogota',
        'barrio' => 'Centro',
        'zona' => 'Urbana',
        'correo' => 'newadmin@test.com',
        'contraseña' => 'password123',
    ];

    public function test_create_admin_user(): void
    {
        Empresa::factory()->create();
        $token = $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/users', $this->validData, $this->authHeaders($token));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Administrador registrado exitosamente.',
            ]);

        $this->assertDatabaseHas('users', ['correo' => 'newadmin@test.com']);
    }

    public function test_create_user_duplicate_cedula(): void
    {
        Empresa::factory()->create();
        $token = $this->actingAsAdmin();

        $this->postJson('/api/v1/users', $this->validData, $this->authHeaders($token));

        $data = $this->validData;
        $data['correo'] = 'other@test.com';

        $response = $this->postJson('/api/v1/users', $data, $this->authHeaders($token));

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Cédula ya registrada.',
            ]);
    }

    public function test_create_user_missing_fields(): void
    {
        $token = $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/users', [], $this->authHeaders($token));

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name', 'No_document', 'correo', 'contraseña',
                'nacimiento', 'direccion', 'municipio',
            ]);
    }

    public function test_list_users(): void
    {
        $token = $this->actingAsAdmin();

        $response = $this->getJson('/api/v1/users', $this->authHeaders($token));

        $response->assertStatus(200);
    }

    public function test_update_user(): void
    {
        $empresa = Empresa::factory()->create();
        $info = InformacionUser::factory()->create();
        $user = User::factory()->create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
        ]);

        $token = $this->loginAs($user);

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'id' => $user->id,
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
            'correo' => 'updated@test.com',
            'contraseña' => 'newpass123',
            'name' => 'Updated Name',
            'No_document' => '9999999999',
            'type_doc' => 'CC',
            'celular' => '3001234567',
            'nacimiento' => '1990-01-01',
            'direccion' => 'Calle 2 #2-2',
            'municipio' => 'Bogota',
            'departamento' => 'Bogota',
            'barrio' => 'Norte',
            'zona' => 'Urbana',
        ], $this->authHeaders($token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_disable_user(): void
    {
        $empresa = Empresa::factory()->create();
        $info = InformacionUser::factory()->create();
        $user = User::factory()->create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
        ]);

        $token = $this->loginAs($user);

        $response = $this->deleteJson("/api/v1/users/{$user->id}", [], $this->authHeaders($token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $user->refresh();
        $this->assertEquals(0, $user->estado);
    }

    public function test_show_user(): void
    {
        $empresa = Empresa::factory()->create();
        $info = InformacionUser::factory()->create();
        $user = User::factory()->create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
        ]);

        $token = $this->loginAs($user);

        $response = $this->getJson("/api/v1/users/{$user->id}", $this->authHeaders($token));

        $response->assertStatus(200);
    }

    public function test_create_user_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/users', $this->validData);

        $response->assertStatus(401);
    }
}
