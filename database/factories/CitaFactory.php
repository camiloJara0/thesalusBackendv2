<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Cita;

class CitaFactory extends Factory
{
    protected $model = Cita::class;

    public function definition(): array
    {
        $fecha = $this->faker->dateTimeBetween('-30 days', '+30 days');
        return [
            'id_paciente' => null,
            'id_medico' => null,
            'id_servicio' => null,
            'motivo' => $this->faker->sentence(),
            'fecha' => $fecha->format('Y-m-d'),
            'hora' => $fecha->format('H:i'),
            'estado' => 'inactiva',
            'motivo_cancelacion' => null,
        ];
    }

    public function activa(): static
    {
        return $this->state(fn () => ['estado' => 'activa']);
    }

    public function cancelada(): static
    {
        return $this->state(fn () => [
            'estado' => 'cancelada',
            'motivo_cancelacion' => $this->faker->sentence(),
        ]);
    }
}
