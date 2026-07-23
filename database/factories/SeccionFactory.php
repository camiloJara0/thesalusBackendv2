<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Secciones;

class SeccionFactory extends Factory
{
    protected $model = Secciones::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->randomElement([
                'Pacientes', 'Profesionales', 'Citas', 'Historia Clinica',
                'Kardex', 'Insumos', 'Medicamentos', 'Reportes',
                'Configuracion', 'Facturacion', 'Convenios', 'Codigos',
            ]),
        ];
    }
}
