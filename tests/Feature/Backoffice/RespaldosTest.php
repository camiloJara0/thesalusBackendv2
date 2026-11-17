<?php

namespace Tests\Feature\Backoffice;

use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Tests\Traits\WithApiUser;

class RespaldosTest extends TestCase
{
    use WithApiUser;

    private string $archivoTest = 'respaldo-test-unitario.sql';

    protected function tearDown(): void
    {
        @unlink(storage_path('app/backups/' . $this->archivoTest));
        parent::tearDown();
    }

    private function sembrarRespaldo(): string
    {
        if (!is_dir(storage_path('app/backups'))) {
            File::makeDirectory(storage_path('app/backups'), 0755, true);
        }
        $ruta = storage_path('app/backups/' . $this->archivoTest);
        file_put_contents($ruta, 'SELECT 1;');
        return $ruta;
    }

    public function test_index_lista_respaldos(): void
    {
        $this->sembrarRespaldo();
        $token = $this->loginAsSuperAdmin();

        $response = $this->getJson('/api/v1/backoffice/respaldos', $this->authHeaders($token));

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertContains($this->archivoTest, collect($response->json('data'))->pluck('archivo')->all());
    }

    public function test_descarga_respaldo_valido(): void
    {
        $this->sembrarRespaldo();
        $token = $this->loginAsSuperAdmin();

        $response = $this->get("/api/v1/backoffice/respaldos/{$this->archivoTest}/descargar", $this->authHeaders($token));

        $response->assertStatus(200);
        $response->assertDownload($this->archivoTest);
    }

    public function test_descarga_rechaza_nombre_invalido_o_inexistente(): void
    {
        $token = $this->loginAsSuperAdmin();

        $this->getJson('/api/v1/backoffice/respaldos/malicioso.txt/descargar', $this->authHeaders($token))
            ->assertStatus(404);

        $this->getJson('/api/v1/backoffice/respaldos/no-existe.sql/descargar', $this->authHeaders($token))
            ->assertStatus(404);
    }

    public function test_store_responde_501_sin_mysqldump_path(): void
    {
        config(['database.mysqldump_path' => null]);
        $token = $this->loginAsSuperAdmin();

        $this->postJson('/api/v1/backoffice/respaldos', [], $this->authHeaders($token))
            ->assertStatus(501);
    }

    public function test_respaldos_devuelve_403_para_admin(): void
    {
        $token = $this->actingAsAdmin();

        $this->getJson('/api/v1/backoffice/respaldos', $this->authHeaders($token))
            ->assertStatus(403);
    }
}
