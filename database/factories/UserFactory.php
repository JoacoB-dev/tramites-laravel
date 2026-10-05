<?php

namespace Database\Factories;

use App\Enums\Rol;
use App\Models\Profesional;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'rol' => Rol::Profesional,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function empleado(): static
    {
        return $this->state(['rol' => Rol::Empleado]);
    }

    public function admin(): static
    {
        return $this->state(['rol' => Rol::Admin]);
    }

    /** Usuario profesional con su ficha de matriculado. */
    public function conProfesional(array $atributos = []): static
    {
        return $this->state(['rol' => Rol::Profesional])
            ->has(Profesional::factory()->state($atributos), 'profesional');
    }
}
