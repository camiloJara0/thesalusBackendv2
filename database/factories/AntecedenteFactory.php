<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Antecedente;

class AntecedenteFactory extends Factory
{
    protected $model = Antecedente::class;

    public function definition(): array
    {
        return [
            'id_paciente' => null,
            'tipo' => $this->faker->randomElement([
                'Enfermedades', 'Quirurgicos', 'Alergias', 'Familiares',
                'Traumaticos', 'Ginecoobstetricos', 'Toxicos', 'Farmacologicos',
            ]),
            'descripcion' => $this->faker->sentence(),
        ];
    }
}
