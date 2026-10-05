<?php

namespace Database\Factories;

use App\Enums\EstadoTramite;
use App\Models\Profesional;
use App\Models\TipoTramite;
use App\Models\Tramite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tramite>
 */
class TramiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'profesional_id' => Profesional::factory(),
            'tipo_tramite_id' => TipoTramite::factory(),
            'estado' => EstadoTramite::Iniciado,
            'descripcion' => fake()->optional()->sentence(),
        ];
    }

    public function configure(): static
    {
        // Igual que en GestorTramites: el número sale del id
        return $this->afterCreating(function (Tramite $tramite) {
            if ($tramite->numero === null) {
                $tramite->update(['numero' => Tramite::generarNumero($tramite->id, $tramite->created_at->year)]);
            }
        });
    }

    public function enEstado(EstadoTramite $estado): static
    {
        return $this->state([
            'estado' => $estado,
            'resuelto_en' => $estado->esFinal() ? now() : null,
        ]);
    }
}
