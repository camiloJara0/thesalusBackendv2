<?php

namespace App\Support;

use App\Models\Cita;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Acceso
{
    protected static bool $resuelto = false;
    protected static ?int $usuarioId = null;
    protected static ?int $idProfesional = null;
    protected static array $permisos = [];

    public static function usuario()
    {
        return Auth::user();
    }

    public static function esAdmin(): bool
    {
        return self::usuario()?->rol === 'Admin';
    }

    public static function esProfesional(): bool
    {
        return self::usuario()?->rol === 'Profesional';
    }

    public static function esSuperAdmin(): bool
    {
        return self::usuario()?->rol === 'SuperAdmin';
    }

    public static function limpiar(): void
    {
        self::$resuelto = false;
        self::$usuarioId = null;
        self::$idProfesional = null;
        self::$permisos = [];
    }

    public static function idProfesional(): ?int
    {
        self::resolver();
        return self::$idProfesional;
    }

    public static function permisosEfectivos(): array
    {
        self::resolver();
        return self::$permisos;
    }

    public static function tienePermiso(string $nombre): bool
    {
        return in_array($nombre, self::permisosEfectivos(), true);
    }

    public static function puedeVerTodosPacientes(): bool
    {
        return self::esAdmin() || self::tienePermiso('ListaPacientes');
    }

    public static function scopePacientes($query)
    {
        if (self::puedeVerTodosPacientes()) {
            return $query;
        }

        $idMedico = self::idProfesional();

        if ($idMedico === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('citas', function ($q) use ($idMedico) {
            $q->where('id_medico', $idMedico);
        });
    }

    public static function scopeAnalisis($query)
    {
        if (self::puedeVerTodosPacientes()) {
            return $query;
        }

        $idMedico = self::idProfesional();

        if ($idMedico === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('historia.paciente', function ($q) use ($idMedico) {
            $q->whereHas('citas', function ($cq) use ($idMedico) {
                $cq->where('id_medico', $idMedico);
            });
        });
    }

    public static function puedeVerPaciente(int $idPaciente): bool
    {
        if (self::puedeVerTodosPacientes()) {
            return true;
        }

        $idMedico = self::idProfesional();

        if ($idMedico === null || $idPaciente <= 0) {
            return false;
        }

        return Cita::where('id_paciente', $idPaciente)
            ->where('id_medico', $idMedico)
            ->exists();
    }

    public static function puedeVerAnalisis(int $idAnalisis): bool
    {
        if (self::puedeVerTodosPacientes()) {
            return true;
        }

        $idMedico = self::idProfesional();

        if ($idMedico === null || $idAnalisis <= 0) {
            return false;
        }

        $idPaciente = DB::table('analises')
            ->join('historia__clinicas', 'analises.id_historia', '=', 'historia__clinicas.id')
            ->where('analises.id', $idAnalisis)
            ->value('historia__clinicas.id_paciente');

        if (!$idPaciente) {
            return false;
        }

        return Cita::where('id_paciente', $idPaciente)
            ->where('id_medico', $idMedico)
            ->exists();
    }

    public static function sanitizarFiltrosExport(array $filtros): ?array
    {
        if (self::puedeVerTodosPacientes()) {
            return $filtros;
        }

        if (!empty($filtros['id_paciente']) && !self::puedeVerPaciente((int) $filtros['id_paciente'])) {
            return null;
        }

        $idMedico = self::idProfesional();

        if ($idMedico === null) {
            return null;
        }

        $filtros['id_profesional'] = $idMedico;

        return $filtros;
    }

    protected static function resolver(): void
    {
        $usuario = self::usuario();
        $usuarioId = $usuario?->id;

        if (self::$resuelto && self::$usuarioId === $usuarioId) {
            return;
        }

        self::$resuelto = true;
        self::$usuarioId = $usuarioId;
        self::$idProfesional = null;
        self::$permisos = array_values(config('permisos.base', []));

        if (!$usuario) {
            return;
        }

        if (self::esAdmin() || self::esSuperAdmin()) {
            self::$permisos = array_values(array_unique(array_merge(
                self::$permisos,
                DB::table('secciones')->pluck('nombre')->all()
            )));
            return;
        }

        if (!self::esProfesional()) {
            return;
        }

        $profesional = DB::table('profesionals')
            ->where('id_infoUsuario', $usuario->id_infoUsuario)
            ->first();

        if (!$profesional) {
            return;
        }

        self::$idProfesional = (int) $profesional->id;

        $porProfesion = DB::table('profesions_has_permisos')
            ->join('secciones', 'profesions_has_permisos.id_seccion', '=', 'secciones.id')
            ->where('profesions_has_permisos.id_profesion', $profesional->id_profesion)
            ->pluck('secciones.nombre')
            ->all();

        $individuales = DB::table('profesional_has_permisos')
            ->join('secciones', 'profesional_has_permisos.id_seccion', '=', 'secciones.id')
            ->where('profesional_has_permisos.id_profesional', $profesional->id)
            ->where('usado', 0)
            ->pluck('secciones.nombre')
            ->all();

        self::$permisos = array_values(array_unique(array_merge(
            self::$permisos,
            $porProfesion,
            $individuales
        )));
    }
}
