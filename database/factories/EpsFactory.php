<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Eps;

class EpsFactory extends Factory
{
    protected $model = Eps::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->numerify('EPS###'),
            'nombre' => $this->faker->company() . ' EPS',
            'nit' => $this->faker->unique()->numerify('800######'),
            'estado' => 1,
        ];
    }
}
