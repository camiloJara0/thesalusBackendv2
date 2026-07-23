<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\InformacionUser;

class InformacionUserFactory extends Factory
{
    protected $model = InformacionUser::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'No_document' => $this->faker->unique()->numerify('##########'),
            'type_doc' => $this->faker->randomElement(['CC', 'CE', 'TI', 'PA']),
            'celular' => $this->faker->numerify('3#########'),
            'telefono' => null,
            'nacimiento' => $this->faker->date('Y-m-d', '2005-01-01'),
            'direccion' => $this->faker->streetAddress(),
            'municipio' => $this->faker->city(),
            'departamento' => $this->faker->state(),
            'barrio' => $this->faker->word(),
            'zona' => $this->faker->randomElement(['Urbana', 'Rural']),
            'telefono' => null,
            'estado' => 1,
        ];
    }
}
