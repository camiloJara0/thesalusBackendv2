<?php

namespace App\Support;

use App\Models\Cita;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Registro transversal de auditoria de citas (cita_auditorias).
 * Nunca lanza excepciones: la auditoria jamas interrumpe la operacion.
 */
class CitaAuditoria
{
    /**
     * Registrar un evento de auditoria sobre una cita existente.
     *
     * @param  Cita|int|null  $cita   Modelo o id de la cita
     * @param  string         $accion creada | editada | cancelada | realizada
     * @param  string|null    $estadoAnterior
     * @param  string|null    $estadoNuevo
     * @param  string|null    $detalle  motivo de edicion/cancelacion u observacion
     */
    public static function registrar($cita, string $accion, ?string $estadoAnterior = null, ?string $estadoNuevo = null, ?string $detalle = null): void
    {
        try {
            $idCita = $cita instanceof Cita ? $cita->id : $cita;
            if (!$idCita) {
                return;
            }

            $usuario = Auth::user();

            \App\Models\CitaAuditoria::create([
                'id_cita'         => $idCita,
                'accion'          => $accion,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo'    => $estadoNuevo,
                'id_usuario'      => $usuario?->id,
                'usuario'         => $usuario?->correo ?? $usuario?->name ?? 'sistema',
                'detalle'         => $detalle,
                'created_at'      => now(),
            ]);
        } catch (Throwable $e) {
            // silencioso
        }
    }

    /**
     * Marcar una cita como Realizada, vincular el analisis y auditar la transicion.
     * Reemplaza el patron repetido Cita::where(...)->update(['estado' => 'Realizada', ...]).
     */
    public static function marcarRealizada($citaId, $analisisId): void
    {
        try {
            if (!$citaId) {
                return;
            }

            $cita = Cita::find($citaId);
            if (!$cita) {
                return;
            }

            $anterior = $cita->estado;

            if ($cita->estado !== 'Realizada') {
                $cita->estado = 'Realizada';
                $cita->id_analisis = $analisisId;
                $cita->save();

                self::registrar($cita, 'realizada', $anterior, 'Realizada', $analisisId ? "Analisis vinculado: #{$analisisId}" : null);
            }
        } catch (Throwable $e) {
            // silencioso
        }
    }
}
