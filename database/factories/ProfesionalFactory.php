<?php

namespace Database\Factories;

use App\Enums\EstadoMatricula;
use App\Models\Profesional;
use App\Support\Cuit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profesional>
 */
class ProfesionalFactory extends Factory
{
    /** Profesión => prefijo de matrícula */
    private const PROFESIONES = [
        'Ingeniero/a civil' => 'IC',
        'Arquitecto/a' => 'AR',
        'Maestro/a mayor de obras' => 'MMO',
        'Ingeniero/a electricista' => 'IE',
        'Agrimensor/a' => 'AG',
    ];

    public function definition(): array
    {
        $profesion = fake()->randomElement(array_keys(self::PROFESIONES));
        $mujer = fake()->boolean();
        $dni = fake()->unique()->numberBetween(20_000_000, 45_000_000);

        return [
            'matricula' => self::PROFESIONES[$profesion].'-'.fake()->unique()->numberBetween(1000, 99999),
            'apellido' => fake()->lastName(),
            'nombre' => fake()->firstName($mujer ? 'female' : 'male'),
            'cuit' => Cuit::desdeDni($dni, $mujer ? '27' : '20'),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => fake()->numerify('11 ####-####'),
            'domicilio' => fake()->streetAddress(),
            'localidad' => fake()->randomElement(['Villa Ficticia', 'San Ejemplo', 'Puerto Demo', 'Las Pruebas', 'Monte Mock']),
            'profesion' => $profesion,
            'fecha_matriculacion' => fake()->dateTimeBetween('-25 years', '-2 months'),
            'estado' => EstadoMatricula::Activa,
        ];
    }

    public function suspendida(string $motivo = 'Deuda de cuotas'): static
    {
        return $this->state(['estado' => EstadoMatricula::Suspendida, 'motivo_estado' => $motivo]);
    }

    public function baja(string $motivo = 'Baja a pedido del profesional'): static
    {
        return $this->state(['estado' => EstadoMatricula::Baja, 'motivo_estado' => $motivo]);
    }
}
