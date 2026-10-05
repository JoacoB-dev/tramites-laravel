<?php

namespace Database\Factories;

use App\Models\Cuota;
use App\Models\Profesional;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cuota>
 */
class CuotaFactory extends Factory
{
    public function definition(): array
    {
        $mes = CarbonImmutable::now()->startOfMonth();

        return [
            'profesional_id' => Profesional::factory(),
            'periodo' => $mes->format('Y-m'),
            'importe' => 18500,
            'vencimiento' => today()->addDays(10),
        ];
    }

    /** Período en el pasado, ya vencido y sin pagar. */
    public function vencida(int $mesesAtras = 2): static
    {
        $mes = CarbonImmutable::now()->startOfMonth()->subMonths($mesesAtras);

        return $this->state([
            'periodo' => $mes->format('Y-m'),
            'vencimiento' => $mes->setDay(10),
        ]);
    }

    public function pagada(): static
    {
        return $this->state(['pagada_en' => now(), 'medio_pago' => 'transferencia']);
    }
}
