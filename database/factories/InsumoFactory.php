<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Insumo;

class InsumoFactory extends Factory
{
    protected $model = Insumo::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->word(),
            'categoria' => $this->faker->randomElement(['Medicamento', 'Insumo', 'Equipo']),
            'activo' => $this->faker->optional()->word(),
            'receta' => $this->faker->boolean(),
            'unidad' => $this->faker->randomElement(['Caja', 'Frasco', 'Unidad', 'Blister']),
            'stock' => $this->faker->numberBetween(0, 100),
            'lote' => $this->faker->optional()->bothify('LOT-####'),
            'vencimiento' => $this->faker->optional()->dateTimeBetween('+1 month', '+1 year'),
            'ubicacion' => $this->faker->optional()->word(),
            'estado' => 'Activo',
        ];
    }
}
