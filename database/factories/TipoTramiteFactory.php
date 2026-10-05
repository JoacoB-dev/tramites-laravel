<?php

namespace Database\Factories;

use App\Models\TipoTramite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoTramite>
 */
class TipoTramiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => 'tipo_'.fake()->unique()->lexify('??????'),
            'nombre' => 'Trámite '.fake()->words(2, true),
            'descripcion' => fake()->sentence(),
            'requiere_adjunto' => false,
            'arancel' => 0,
            'dias_estimados' => 5,
            'activo' => true,
        ];
    }

    public function visado(): static
    {
        return $this->state([
            'codigo' => TipoTramite::VISADO_PLANOS,
            'nombre' => 'Visado de planos',
            'requiere_adjunto' => true,
            'arancel' => 25000,
        ]);
    }

    public function certificado(): static
    {
        return $this->state([
            'codigo' => TipoTramite::CERTIFICADO_VIGENCIA,
            'nombre' => 'Certificado de matrícula vigente',
        ]);
    }

    public function cambioDomicilio(): static
    {
        return $this->state([
            'codigo' => TipoTramite::CAMBIO_DOMICILIO,
            'nombre' => 'Cambio de domicilio profesional',
        ]);
    }
}
