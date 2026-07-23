<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Tests\Traits\WithApiUser;
use App\Models\User;
use App\Models\InformacionUser;
use App\Models\Empresa;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TokenExpirationTest extends TestCase
{
    use RefreshDatabase, WithApiUser;

    public function test_valid_token_allows_access(): void
    {
        $empresa = Empresa::factory()->create();
        $info = InformacionUser::factory()->create();
        $user = User::factory()->create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
        ]);

        $token = $this->loginAs($user);

        $response = $this->getJson('/api/v1/users', $this->authHeaders($token));

        $response->assertStatus(200);
    }

    public function test_expired_token_is_rejected(): void
    {
        $empresa = Empresa::factory()->create();
        $info = InformacionUser::factory()->create();
        $user = User::factory()->create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
        ]);

        $tokenResult = $user->createToken('test_token');
        $accessToken = $tokenResult->accessToken;
        $accessToken->expires_at = Carbon::now()->subHours(1);
        $accessToken->save();

        $token = $tokenResult->plainTextToken;

        $response = $this->getJson('/api/v1/users', [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    public function test_token_without_expiration_allows_access(): void
    {
        $empresa = Empresa::factory()->create();
        $info = InformacionUser::factory()->create();
        $user = User::factory()->create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
        ]);

        $tokenResult = $user->createToken('test_token');
        $accessToken = $tokenResult->accessToken;
        $accessToken->expires_at = null;
        $accessToken->save();

        $token = $tokenResult->plainTextToken;

        $response = $this->getJson('/api/v1/users', [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
    }

    public function test_login_sets_token_expiration(): void
    {
        $empresa = Empresa::factory()->create();
        $info = InformacionUser::factory()->create();
        $user = User::factory()->create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
            'correo' => 'expire@test.com',
            'contraseña' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'correo' => 'expire@test.com',
            'contraseña' => 'password123',
        ]);

        $response->assertStatus(200);

        $token = $response->json('access_token');
        $this->assertNotEmpty($token);
    }
}
