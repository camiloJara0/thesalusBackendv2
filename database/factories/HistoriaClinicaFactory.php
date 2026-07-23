<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Historia_Clinica;

class HistoriaClinicaFactory extends Factory
{
    protected $model = Historia_Clinica::class;

    public function definition(): array
    {
        return [
            'id_paciente' => null,
            'fecha_historia' => $this->faker->date('Y-m-d'),
        ];
    }
}
