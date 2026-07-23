<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Paciente;

class PacienteFactory extends Factory
{
    protected $model = Paciente::class;

    public function definition(): array
    {
        return [
            'id_eps' => null,
            'id_infoUsuario' => null,
            'genero' => $this->faker->randomElement(['M', 'F']),
            'sexo' => $this->faker->randomElement(['M', 'F']),
            'regimen' => $this->faker->randomElement(['Contributivo', 'Subsidiado']),
            'vulnerabilidad' => $this->faker->randomElement(['Ninguna', 'Baja', 'Media', 'Alta']),
            'estado' => 1,
        ];
    }
}
