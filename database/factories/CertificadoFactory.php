<?php

namespace Database\Factories;

use App\Models\Certificado;
use App\Models\Profesional;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificado>
 */
class CertificadoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => Certificado::generarCodigo(),
            'profesional_id' => Profesional::factory(),
            'emitido_en' => now(),
            'valido_hasta' => today()->addDays(90),
        ];
    }

    public function vencido(): static
    {
        return $this->state(['emitido_en' => now()->subDays(120), 'valido_hasta' => today()->subDays(30)]);
    }

    public function anulado(): static
    {
        return $this->state(['anulado_en' => now(), 'motivo_anulacion' => 'Emitido por error']);
    }
}
