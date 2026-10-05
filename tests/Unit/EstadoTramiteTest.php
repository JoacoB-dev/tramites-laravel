<?php

namespace Tests\Unit;

use App\Enums\EstadoTramite;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EstadoTramiteTest extends TestCase
{
    #[Test]
    public function el_flujo_permite_solo_las_transiciones_definidas(): void
    {
        $this->assertTrue(EstadoTramite::Iniciado->puedePasarA(EstadoTramite::EnRevision));
        $this->assertTrue(EstadoTramite::EnRevision->puedePasarA(EstadoTramite::Observado));
        $this->assertTrue(EstadoTramite::EnRevision->puedePasarA(EstadoTramite::Aprobado));
        $this->assertTrue(EstadoTramite::EnRevision->puedePasarA(EstadoTramite::Rechazado));
        $this->assertTrue(EstadoTramite::Observado->puedePasarA(EstadoTramite::EnRevision));

        // Saltear la revisión no está permitido
        $this->assertFalse(EstadoTramite::Iniciado->puedePasarA(EstadoTramite::Aprobado));
        $this->assertFalse(EstadoTramite::Observado->puedePasarA(EstadoTramite::Aprobado));
    }

    #[Test]
    public function aprobado_y_rechazado_son_finales(): void
    {
        foreach ([EstadoTramite::Aprobado, EstadoTramite::Rechazado] as $final) {
            $this->assertTrue($final->esFinal());
            foreach (EstadoTramite::cases() as $otro) {
                $this->assertFalse($final->puedePasarA($otro));
            }
        }
        $this->assertFalse(EstadoTramite::EnRevision->esFinal());
    }

    #[Test]
    public function observar_y_rechazar_requieren_observacion(): void
    {
        $this->assertTrue(EstadoTramite::Observado->requiereObservacion());
        $this->assertTrue(EstadoTramite::Rechazado->requiereObservacion());
        $this->assertFalse(EstadoTramite::Aprobado->requiereObservacion());
    }
}
