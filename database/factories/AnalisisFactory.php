<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Analisis;

class AnalisisFactory extends Factory
{
    protected $model = Analisis::class;

    public function definition(): array
    {
        return [
            'id_historia' => null,
            'id_medico' => null,
            'id_servicio' => null,
            'motivo' => $this->faker->sentence(),
            'analisis' => $this->faker->paragraph(),
            'observacion' => $this->faker->sentence(),
            'tipoAnalisis' => $this->faker->randomElement(['Evolucion', 'Valoracion', 'Control']),
            'tratamiento' => $this->faker->sentence(),
        ];
    }
}
