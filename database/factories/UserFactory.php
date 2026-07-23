<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id_empresa' => null,
            'id_infoUsuario' => null,
            'correo' => $this->faker->unique()->safeEmail(),
            'contraseña' => Hash::make('password123'),
            'rol' => 'Admin',
            'estado' => 1,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['rol' => 'Admin']);
    }

    public function profesional(): static
    {
        return $this->state(fn () => ['rol' => 'Profesional']);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['estado' => 0]);
    }
}
