<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Profesion;

class ProfesionFactory extends Factory
{
    protected $model = Profesion::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->bothify('??##'),
            'nombre' => $this->faker->randomElement([
                'Medico General', 'Enfermera', 'Fisioterapeuta',
                'Terapeuta Ocupacional', 'Psicologo', 'Nutricionista',
                'Trabajador Social', 'Odontologo', 'Terapeuta del Lenguaje',
            ]),
            'estado' => 1,
        ];
    }
}
