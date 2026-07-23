<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Diagnostico;

class DiagnosticoFactory extends Factory
{
    protected $model = Diagnostico::class;

    public function definition(): array
    {
        return [
            'id_analisis' => null,
            'descripcion' => $this->faker->words(3, true),
            'codigo' => $this->faker->bothify('??#.##'),
        ];
    }
}
