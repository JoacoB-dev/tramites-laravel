<?php

namespace Tests\Unit;

use App\Enums\EstadoCuota;
use App\Models\Cuota;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Usa el TestCase de Laravel (necesita fechas y traducciones), pero no la base. */
class CuotaTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function cuota(string $vencimiento, ?string $pagadaEn = null): Cuota
    {
        return new Cuota(['periodo' => '2026-09', 'importe' => 1000, 'vencimiento' => $vencimiento, 'pagada_en' => $pagadaEn]);
    }

    #[Test]
    public function calcula_el_estado_segun_pago_y_vencimiento(): void
    {
        Carbon::setTestNow('2026-09-15 12:00');

        $this->assertSame(EstadoCuota::Vencida, $this->cuota('2026-09-10')->estado());
        $this->assertSame(EstadoCuota::Pendiente, $this->cuota('2026-09-15')->estado(), 'El día del vencimiento todavía no está vencida');
        $this->assertSame(EstadoCuota::Pendiente, $this->cuota('2026-10-10')->estado());
        $this->assertSame(EstadoCuota::Pagada, $this->cuota('2026-09-10', '2026-09-20 10:00')->estado());
    }

    #[Test]
    public function muestra_el_periodo_en_castellano(): void
    {
        $this->assertSame('septiembre 2026', $this->cuota('2026-09-10')->periodoLegible());
    }
}
