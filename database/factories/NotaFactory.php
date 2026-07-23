<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Nota;

class NotaFactory extends Factory
{
    protected $model = Nota::class;

    public function definition(): array
    {
        return [
            'id_paciente' => null,
            'id_procedimiento' => null,
            'id_profesional' => null,
            'direccion' => $this->faker->streetAddress(),
            'fecha_nota' => $this->faker->date('Y-m-d'),
            'hora_nota' => $this->faker->time('H:i:s'),
            'nota' => $this->faker->paragraph(),
            'tipoAnalisis' => $this->faker->randomElement(['Evolucion', 'Valoracion', 'Control']),
        ];
    }
}
