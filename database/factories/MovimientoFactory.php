<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Movimiento;

class MovimientoFactory extends Factory
{
    protected $model = Movimiento::class;

    public function definition(): array
    {
        return [
            'id_insumo' => null,
            'id_medico' => null,
            'id_analisis' => null,
            'cantidadMovimiento' => $this->faker->numberBetween(1, 10),
            'tipoMovimiento' => $this->faker->randomElement(['Entrada', 'Salida', 'Devolucion']),
            'fechaMovimiento' => $this->faker->date('Y-m-d'),
        ];
    }
}
