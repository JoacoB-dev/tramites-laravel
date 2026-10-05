<?php

namespace Tests;

use App\Models\Profesional;
use App\Models\TipoTramite;
use App\Models\User;
use Database\Seeders\TipoTramiteSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Los tests no necesitan los assets compilados por Vite
        $this->withoutVite();
    }

    protected function crearTiposDeTramite(): void
    {
        $this->seed(TipoTramiteSeeder::class);
    }

    protected function tipo(string $codigo): TipoTramite
    {
        return TipoTramite::where('codigo', $codigo)->firstOrFail();
    }

    /** Usuario con rol profesional y su ficha de matriculado. */
    protected function profesional(array $atributos = []): User
    {
        $usuario = User::factory()->create();
        Profesional::factory()->create(['user_id' => $usuario->id] + $atributos);

        return $usuario->fresh('profesional');
    }

    protected function empleado(): User
    {
        return User::factory()->empleado()->create();
    }

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
