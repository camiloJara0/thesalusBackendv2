<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\CodigoVerificacion;

class CodigoVerificacionFactory extends Factory
{
    protected $model = CodigoVerificacion::class;

    public function definition(): array
    {
        return [
            'correo' => $this->faker->safeEmail(),
            'codigo' => $this->faker->numerify('######'),
            'expira_en' => now()->addMinutes(15),
            'usado' => false,
        ];
    }

    public function used(): static
    {
        return $this->state(fn () => ['usado' => true]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expira_en' => now()->subMinutes(5)]);
    }
}
