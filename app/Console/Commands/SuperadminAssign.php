<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Console\Command;

class SuperadminAssign extends Command
{
    protected $signature = 'superadmin:assign {correo : Correo del usuario que recibirá el rol SuperAdmin}';

    protected $description = 'Asigna el rol SuperAdmin a un usuario. Única vía de alta de SuperAdmin (no existe por HTTP).';

    public function handle(): int
    {
        $correo = $this->argument('correo');

        $user = User::where('correo', $correo)->first();

        if (!$user) {
            $this->error("Usuario no encontrado: {$correo}");
            return self::FAILURE;
        }

        if ($user->rol === 'SuperAdmin') {
            $this->info("El usuario {$correo} ya es SuperAdmin.");
            return self::SUCCESS;
        }

        $rolAnterior = $user->rol;
        $user->rol = 'SuperAdmin';
        $user->save();

        Audit::log('superadmin.asignado', [
            'user_id' => $user->id,
            'correo' => $user->correo,
            'modelo' => 'users',
            'modelo_id' => $user->id,
            'descripcion' => 'Rol SuperAdmin asignado via CLI',
            'datos' => ['rol_anterior' => $rolAnterior],
        ]);

        $this->info("OK: {$correo} ahora es SuperAdmin (rol anterior: {$rolAnterior}).");
        $this->line('Ingrese al backoffice desde el frontend con este usuario.');

        return self::SUCCESS;
    }
}
