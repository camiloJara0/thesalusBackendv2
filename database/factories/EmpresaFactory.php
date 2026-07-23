<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Empresa;

class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->company(),
            'no_identificacion' => $this->faker->unique()->numerify('900######'),
            'DV' => (string) $this->faker->numberBetween(0, 9),
            'direccion' => $this->faker->streetAddress(),
            'municipio' => $this->faker->city(),
            'pais' => 'Colombia',
            'telefono' => $this->faker->numerify('3#########'),
            'lenguaje' => 'es',
            'tipoDocumento' => 'NIT',
            'tipoEntorno' => 'Pruebas',
            'tipoMoneda' => 'COP',
            'tipoOperacion' => 'Servicios',
            'tipoOrganizacion' => 'EPS',
            'tipoRegimen' => 'General',
            'tipoResponsabilidad' => 'IVA',
            'impuesto' => 'IVA',
            'registroMercantil' => $this->faker->numerify('####'),
            'logo' => null,
            'logoLogin' => null,
            'JPG' => null,
            'estado' => 1,
        ];
    }
}
