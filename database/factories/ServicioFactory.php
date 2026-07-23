<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Servicio;

class ServicioFactory extends Factory
{
    protected $model = Servicio::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Medicina General', 'Fisioterapia', 'Terapia Ocupacional',
                'Psicologia', 'Nutricion', 'Trabajo Social', 'Odontologia',
            ]),
            'plantilla' => $this->faker->randomElement([
                'evolucion', 'terapia', 'nutricion', 'trabajo_social',
            ]),
        ];
    }
}
