<?php

namespace Tests\Feature\Backoffice;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\WithApiUser;

class MetricasTest extends TestCase
{
    use WithApiUser;

    public function test_middleware_incrementa_contadores_en_cache(): void
    {
        $this->postJson('/api/v1/login', ['correo' => 'nadie@test.com', 'contraseña' => 'mala'])
            ->assertStatus(403);

        $fecha = now()->toDateString();
        $this->assertGreaterThanOrEqual(1, Cache::get("metricas:api:{$fecha}:solicitudes"));
        $this->assertGreaterThanOrEqual(1, Cache::get("metricas:api:{$fecha}:4xx"));
    }

    public function test_flush_consolida_cache_hacia_la_tabla(): void
    {
        Cache::put('metricas:api:2026-11-12:solicitudes', 100, 3600);
        Cache::put('metricas:api:2026-11-12:4xx', 5, 3600);
        Cache::put('metricas:api:2026-11-12:5xx', 2, 3600);
        Cache::put('metricas:api:2026-11-12:latencia_sum', 1000, 3600);
        Cache::put('metricas:api:2026-11-12:latencia_n', 10, 3600);

        $this->artisan('metricas:flush', ['fecha' => '2026-11-12'])->assertExitCode(0);

        $this->assertDatabaseHas('metricas_api_dias', [
            'fecha' => '2026-11-12',
            'solicitudes' => 100,
            'errores_4xx' => 5,
            'errores_5xx' => 2,
            'latencia_promedio_ms' => 100,
        ]);

        $this->assertNull(Cache::get('metricas:api:2026-11-12:solicitudes'));
    }

    public function test_endpoint_metricas_devuelve_serie_y_resumen(): void
    {
        DB::table('metricas_api_dias')->insert([
            ['fecha' => '2026-11-01', 'solicitudes' => 100, 'errores_4xx' => 4, 'errores_5xx' => 1, 'latencia_promedio_ms' => 50, 'updated_at' => now()],
            ['fecha' => '2026-11-02', 'solicitudes' => 200, 'errores_4xx' => 6, 'errores_5xx' => 3, 'latencia_promedio_ms' => 70, 'updated_at' => now()],
        ]);

        $token = $this->loginAsSuperAdmin();

        $response = $this->getJson('/api/v1/backoffice/metricas?desde=2026-11-01&hasta=2026-11-02', $this->authHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('resumen.solicitudes', 300)
            ->assertJsonPath('resumen.errores_4xx', 10)
            ->assertJsonPath('resumen.errores_5xx', 4)
            ->assertJsonPath('resumen.dias', 2);

        $this->assertCount(2, $response->json('serie'));
    }

    public function test_endpoint_metricas_devuelve_403_para_admin(): void
    {
        $token = $this->actingAsAdmin();

        $this->getJson('/api/v1/backoffice/metricas', $this->authHeaders($token))
            ->assertStatus(403);
    }
}
