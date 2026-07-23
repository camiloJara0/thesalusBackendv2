<?php

namespace Tests\Traits;

use App\Models\User;
use App\Models\Empresa;
use App\Models\InformacionUser;
use App\Models\Profesional;
use App\Models\Profesion;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

trait WithApiUser
{
    protected function ensureEmpresaId1(): Empresa
    {
        $empresa = Empresa::find(1);
        if ($empresa) {
            return $empresa;
        }

        DB::table('empresas')->insert([
            'id' => 1,
            'no_identificacion' => '900000000',
            'nombre' => 'Empresa Test',
            'DV' => '0',
            'direccion' => 'Calle 1 #1-1',
            'municipio' => 'Bogota',
            'pais' => 'Colombia',
            'telefono' => '3001234567',
            'lenguaje' => 'es',
            'tipoDocumento' => 'NIT',
            'tipoEntorno' => 'Pruebas',
            'tipoMoneda' => 'COP',
            'tipoOperacion' => 'Servicios',
            'tipoOrganizacion' => 'EPS',
            'tipoRegimen' => 'General',
            'tipoResponsabilidad' => 'IVA',
            'impuesto' => 'IVA',
            'registroMercantil' => '0000',
            'estado' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Empresa::find(1);
    }

    protected function createAdminUser(): User
    {
        $empresa = $this->ensureEmpresaId1();

        $info = InformacionUser::create([
            'name' => 'Admin Test',
            'No_document' => '1000000000',
            'type_doc' => 'CC',
            'celular' => '3001234567',
            'nacimiento' => '1990-01-01',
            'direccion' => 'Calle 1 #1-1',
            'municipio' => 'Bogota',
            'departamento' => 'Bogota',
            'barrio' => 'Centro',
            'zona' => 'Urbana',
        ]);

        $user = User::create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
            'correo' => 'admin@test.com',
            'contraseña' => Hash::make('password123'),
            'rol' => 'Admin',
            'estado' => 1,
        ]);

        return $user;
    }

    protected function createProfesionalUser(): array
    {
        $empresa = Empresa::firstOrCreate(
            ['no_identificacion' => '900000001'],
            [
                'nombre' => 'Empresa Test 2',
                'DV' => '0',
                'direccion' => 'Calle 2 #2-2',
                'municipio' => 'Bogota',
                'pais' => 'Colombia',
                'telefono' => '3001234568',
                'lenguaje' => 'es',
                'tipoDocumento' => 'NIT',
                'tipoEntorno' => 'Pruebas',
                'tipoMoneda' => 'COP',
                'tipoOperacion' => 'Servicios',
                'tipoOrganizacion' => 'EPS',
                'tipoRegimen' => 'General',
                'tipoResponsabilidad' => 'IVA',
                'impuesto' => 'IVA',
                'registroMercantil' => '0001',
            ]
        );

        $info = InformacionUser::create([
            'name' => 'Profesional Test',
            'No_document' => '1000000001',
            'type_doc' => 'CC',
            'celular' => '3001234568',
            'nacimiento' => '1990-01-01',
            'direccion' => 'Calle 2 #2-2',
            'municipio' => 'Bogota',
            'departamento' => 'Bogota',
            'barrio' => 'Centro',
            'zona' => 'Urbana',
        ]);

        $user = User::create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
            'correo' => 'profesional@test.com',
            'contraseña' => Hash::make('password123'),
            'rol' => 'Profesional',
            'estado' => 1,
        ]);

        $profesion = Profesion::firstOrCreate(
            ['nombre' => 'Medico General'],
            ['codigo' => 'MG', 'estado' => 1]
        );

        $profesional = Profesional::create([
            'id_profesion' => $profesion->id,
            'id_infoUsuario' => $info->id,
            'zona_laboral' => 'Urbana',
            'departamento_laboral' => 'Bogota',
            'municipio_laboral' => 'Bogota',
            'estado' => 1,
        ]);

        return ['user' => $user, 'profesional' => $profesional, 'info' => $info, 'empresa' => $empresa];
    }

    protected function loginAs(User $user): string
    {
        $response = $this->postJson('/api/v1/login', [
            'correo' => $user->correo,
            'contraseña' => 'password123',
        ]);

        return $response->json('access_token');
    }

    protected function actingAsAdmin(): string
    {
        $user = $this->createAdminUser();
        return $this->loginAs($user);
    }

    protected function actingAsProfesional(): string
    {
        $data = $this->createProfesionalUser();
        return $this->loginAs($data['user']);
    }

    protected function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }
}
