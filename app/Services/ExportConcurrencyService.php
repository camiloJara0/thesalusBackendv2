<?php

namespace App\Services;

use App\Models\Export;
use Illuminate\Support\Facades\DB;

class ExportConcurrencyService
{
    /**
     * Estados que ocupan cupo: exportación en cola o en ejecución.
     * Estados terminales (lista, fallida, cancelada) NO ocupan cupo.
     */
    public const ESTADOS_ACTIVOS = ['pendiente', 'procesando', 'comprimiendo'];

    public function max(): int
    {
        return max(1, (int) config('exports.max_activas', 6));
    }

    public function activas(): int
    {
        return Export::whereIn('estado', self::ESTADOS_ACTIVOS)->count();
    }

    public function capacidad(): array
    {
        $max = $this->max();
        $activas = $this->activas();

        return [
            'active_exports' => $activas,
            'max_exports' => $max,
            'available_slots' => max(0, $max - $activas),
            'can_export' => $activas < $max,
        ];
    }

    /**
     * Crea una exportación solo si hay cupo disponible.
     *
     * Transacción + SELECT ... FOR UPDATE sobre el conteo garantiza que
     * dos solicitudes simultáneas no superen jamás el límite (evita la
     * condición de carrera: la segunda transacción espera el commit de
     * la primera y recontabiliza con la fila ya insertada).
     *
     * @return array{export: Export|null, active: int, max: int}
     */
    public function crear(array $datos): array
    {
        return DB::transaction(function () use ($datos) {
            $max = $this->max();

            $activas = Export::whereIn('estado', self::ESTADOS_ACTIVOS)
                ->lockForUpdate()
                ->count();

            if ($activas >= $max) {
                return ['export' => null, 'active' => $activas, 'max' => $max];
            }

            $export = Export::create($datos);

            return ['export' => $export, 'active' => $activas + 1, 'max' => $max];
        });
    }

    /**
     * Marca como fallidas las exportaciones activas vencidas para
     * liberar cupos cuando un worker murió sin cambiar el estado.
     * El cálculo siempre se hace sobre datos actuales de la base.
     *
     * @return int
     */
    public function marcarVencidas(): int
    {
        $horas = max(1, (int) config('exports.horas_vencidas', 2));

        return Export::whereIn('estado', self::ESTADOS_ACTIVOS)
            ->where('created_at', '<', now()->subHours($horas))
            ->update([
                'estado' => 'fallida',
                'updated_at' => now(),
            ]);
    }
}
