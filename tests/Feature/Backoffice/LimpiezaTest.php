<?php

namespace Tests\Feature\Backoffice;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\WithApiUser;

class LimpiezaTest extends TestCase
{
    use WithApiUser;

    public function test_temporales_elimina_directorios_y_archivos(): void
    {
        $dir = storage_path('app/temp_pdf_' . uniqid());
        $archivo = storage_path('app/temp_' . uniqid() . '.pdf');
        File::makeDirectory($dir, 0755, true);
        file_put_contents($dir . '/x.pdf', 'basura');
        file_put_contents($archivo, 'basura');

        $token = $this->loginAsSuperAdmin();

        $response = $this->postJson('/api/v1/backoffice/limpieza/temporales', [], $this->authHeaders($token));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertFileDoesNotExist($dir . '/x.pdf');
        $this->assertDirectoryDoesNotExist($dir);
        $this->assertFileDoesNotExist($archivo);
        $this->assertDatabaseHas('audit_logs', ['accion' => 'limpieza.temporales']);
    }

    public function test_logs_trunca_conservando_las_ultimas_lineas(): void
    {
        $ruta = storage_path('logs/laravel.log');
        $original = is_file($ruta) ? file_get_contents($ruta) : null;

        try {
            file_put_contents($ruta, "linea1\nlinea2\nlinea3\nlinea4\nlinea5\n");

            $token = $this->loginAsSuperAdmin();

            $response = $this->postJson('/api/v1/backoffice/limpieza/logs', ['lineas' => 2], $this->authHeaders($token));

            $response->assertStatus(200)
                ->assertJson(['success' => true, 'lineas_antes' => 5, 'lineas_conservadas' => 2]);

            $this->assertSame("linea4\nlinea5\n", file_get_contents($ruta));
            $this->assertDatabaseHas('audit_logs', ['accion' => 'limpieza.logs']);
        } finally {
            if ($original !== null) {
                file_put_contents($ruta, $original);
            } elseif (is_file($ruta)) {
                unlink($ruta);
            }
        }
    }

    public function test_tokens_poda_solo_expirados(): void
    {
        DB::table('personal_access_tokens')->insert([
            ['tokenable_type' => User::class, 'tokenable_id' => 1, 'name' => 'exp', 'token' => hash('sha256', Str::random(40)), 'abilities' => '["*"]', 'expires_at' => now()->subDay(), 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()],
            ['tokenable_type' => User::class, 'tokenable_id' => 1, 'name' => 'vig', 'token' => hash('sha256', Str::random(40)), 'abilities' => '["*"]', 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $token = $this->loginAsSuperAdmin();

        $this->postJson('/api/v1/backoffice/limpieza/tokens', [], $this->authHeaders($token))
            ->assertStatus(200)
            ->assertJson(['success' => true, 'tokens_eliminados' => 1]);

        $this->assertSame(0, DB::table('personal_access_tokens')->whereNotNull('expires_at')->where('expires_at', '<', now())->count());
        $this->assertSame(1, DB::table('personal_access_tokens')->where('name', 'vig')->count());
        $this->assertDatabaseHas('audit_logs', ['accion' => 'limpieza.tokens']);
    }

    public function test_limpieza_devuelve_403_para_admin(): void
    {
        $token = $this->actingAsAdmin();

        $this->postJson('/api/v1/backoffice/limpieza/tokens', [], $this->authHeaders($token))
            ->assertStatus(403);
    }
}
