<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Models\Cuota;
use App\Models\Profesional;
use Carbon\CarbonImmutable;

class GeneradorCuotas
{
    /**
     * Crea la cuota del período para cada matrícula activa que todavía no la tenga.
     * Es idempotente: correrlo dos veces para el mismo mes no duplica cuotas.
     *
     * @param  string  $periodo  AAAA-MM
     * @return int cantidad de cuotas creadas
     */
    public function generar(string $periodo): int
    {
        $mes = CarbonImmutable::createFromFormat('!Y-m', $periodo);
        if ($mes === false || $mes->format('Y-m') !== $periodo) {
            throw new \InvalidArgumentException("Período inválido: {$periodo} (formato AAAA-MM).");
        }

        $vencimiento = $mes->setDay(config('consejo.cuota.dia_vencimiento'));
        $importe = config('consejo.cuota.importe');
        $creadas = 0;

        Profesional::where('estado', EstadoMatricula::Activa)
            ->whereDoesntHave('cuotas', fn ($q) => $q->where('periodo', $periodo))
            ->select('id')
            ->chunkById(200, function ($profesionales) use ($periodo, $vencimiento, $importe, &$creadas) {
                foreach ($profesionales as $profesional) {
                    Cuota::create([
                        'profesional_id' => $profesional->id,
                        'periodo' => $periodo,
                        'importe' => $importe,
                        'vencimiento' => $vencimiento,
                    ]);
                    $creadas++;
                }
            });

        return $creadas;
    }
}
