<?php

namespace Tests\Unit;

use App\Models\Certificado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CertificadoCodigoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function genera_codigos_con_formato_legible_y_sin_caracteres_ambiguos(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $codigo = Certificado::generarCodigo();
            $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/', $codigo);
        }
    }

    #[Test]
    public function normaliza_lo_que_tipea_el_usuario(): void
    {
        $this->assertSame('ABCD-EFGH-JKLM', Certificado::normalizarCodigo(' abcd-efgh-jklm '));
        $this->assertSame('ABSCRIPTCD', Certificado::normalizarCodigo('ab<script>cd'));
    }
}
