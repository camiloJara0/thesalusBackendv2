<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class Audit
{
    /**
     * Registrar una accion en audit_logs. Nunca lanza excepciones:
     * la auditoria no debe romper la operacion principal.
     *
     * Opciones: modelo, modelo_id, descripcion, datos, user_id, correo.
     */
    public static function log(string $accion, array $opciones = []): void
    {
        try {
            $usuario = Auth::user();

            $userId = $opciones['user_id'] ?? $usuario?->id;
            $correo = $opciones['correo'] ?? $usuario?->correo ?? 'sistema';
            $request = request();

            AuditLog::create([
                'user_id' => $userId,
                'correo' => $correo,
                'accion' => $accion,
                'modelo' => $opciones['modelo'] ?? null,
                'modelo_id' => $opciones['modelo_id'] ?? null,
                'descripcion' => Str::limit($opciones['descripcion'] ?? '', 1000, '...'),
                'ip' => $request?->ip(),
                'user_agent' => Str::limit((string) $request?->userAgent(), 500, '...'),
                'datos' => $opciones['datos'] ?? null,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            // silencioso: la auditoria jamas interrumpe la operacion
        }
    }
}
