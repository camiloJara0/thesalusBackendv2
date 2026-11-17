<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\InformacionUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    /**
     * Crea (o actualiza) el usuario SuperAdmin del backoffice.
     *
     * La contraseña viene de .env: SUPERADMIN_PASSWORD (obligatoria) y el
     * correo de SUPERADMIN_CORREO (opcional, con valor por defecto).
     *
     * Ejecutar: php artisan db:seed --class=SuperadminSeeder
     * Si el usuario ya exista, se rota la contraseña y se fuerza rol SuperAdmin.
     */
    public function run(): void
    {
        $correo = config('superadmin.correo', 'superadmin@santaisabel.com');
        $password = config('superadmin.password');

        if (!$password) {
            $this->command?->error('Falta SUPERADMIN_PASSWORD en .env.');
            $this->command?->error('Agrega: SUPERADMIN_PASSWORD=tu_contraseña  y vuelve a ejecutar el seeder.');
            return;
        }

        $empresa = $this->asegurarEmpresa();

        $user = User::where('correo', $correo)->first();

        if ($user) {
            $user->contraseña = Hash::make($password);
            $user->rol = 'SuperAdmin';
            $user->estado = 1;
            if (!$user->id_empresa) {
                $user->id_empresa = $empresa->id;
            }
            if (!$user->id_infoUsuario) {
                $user->id_infoUsuario = $this->crearInformacion($correo)->id;
            }
            $user->save();

            $this->command?->info("SuperAdmin actualizado: {$correo} (contraseña rotada, rol SuperAdmin).");
            return;
        }

        $info = $this->crearInformacion($correo);

        User::create([
            'id_empresa' => $empresa->id,
            'id_infoUsuario' => $info->id,
            'correo' => $correo,
            'contraseña' => Hash::make($password),
            'rol' => 'SuperAdmin',
            'estado' => 1,
        ]);

        $this->command?->info("SuperAdmin creado: {$correo}");
        $this->command?->info('Ingrese al backoffice desde el frontend con este usuario.');
    }

    private function asegurarEmpresa(): Empresa
    {
        $empresa = Empresa::orderBy('id')->first();

        if ($empresa) {
            return $empresa;
        }

        DB::table('empresas')->insert([
            'no_identificacion' => '900000000',
            'nombre' => 'Empresa Principal',
            'DV' => '0',
            'direccion' => 'Calle 1 #1-1',
            'municipio' => 'Bogota',
            'pais' => 'Colombia',
            'telefono' => '3001234567',
            'lenguaje' => 'es',
            'tipoDocumento' => 'NIT',
            'tipoEntorno' => 'Produccion',
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

        return Empresa::orderBy('id')->first();
    }

    private function crearInformacion(string $correo): InformacionUser
    {
        return InformacionUser::create([
            'name' => 'Super Admin',
            'No_document' => 'SA-' . substr(sha1($correo), 0, 15),
            'type_doc' => 'CC',
            'celular' => '3000000000',
            'nacimiento' => '1990-01-01',
            'direccion' => 'Calle 1 #1-1',
            'municipio' => 'Bogota',
            'departamento' => 'Bogota',
            'barrio' => 'Centro',
            'zona' => 'Urbana',
        ]);
    }
}
