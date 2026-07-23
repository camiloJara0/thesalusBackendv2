<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Profesional;

class ProfesionalFactory extends Factory
{
    protected $model = Profesional::class;

    public function definition(): array
    {
        return [
            'id_profesion' => null,
            'id_infoUsuario' => null,
            'zona_laboral' => $this->faker->randomElement(['Urbana', 'Rural']),
            'departamento_laboral' => 'Bogota',
            'municipio_laboral' => 'Bogota',
            'estado' => 1,
            'sello' => null,
        ];
    }
}
