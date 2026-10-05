<?php

namespace App\Console\Commands;

use App\Services\GeneradorCuotas;
use Illuminate\Console\Command;

class GenerarCuotas extends Command
{
    protected $signature = 'cuotas:generar {periodo? : Período AAAA-MM (por defecto, el mes actual)}';

    protected $description = 'Genera la cuota mensual de matrícula para todos los profesionales activos';

    public function handle(GeneradorCuotas $generador): int
    {
        $periodo = $this->argument('periodo') ?? now()->format('Y-m');

        try {
            $creadas = $generador->generar($periodo);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Período {$periodo}: {$creadas} cuota(s) generada(s).");

        return self::SUCCESS;
    }
}
