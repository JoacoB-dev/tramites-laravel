<?php

namespace Tests\Unit;

use App\Support\Cuit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CuitTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function cuitsValidos(): array
    {
        return [
            'persona con guiones' => ['20-12345678-6'],
            'sin guiones' => ['20123456786'],
            'con espacios' => ['20 12345678 6'],
            'persona jurídica (AFIP)' => ['33-69345023-9'],
            'dígito 0 (resultado 11)' => [Cuit::desdeDni(11111111, '20')],
        ];
    }

    /** @return array<string, array{string|null}> */
    public static function cuitsInvalidos(): array
    {
        return [
            'dígito verificador mal' => ['20-12345678-5'],
            'pocos dígitos' => ['20-1234567-6'],
            'demasiados dígitos' => ['20-123456789-6'],
            'prefijo inexistente' => ['21-12345678-6'],
            'letras' => ['20-ABCDEFGH-6'],
            'vacío' => [''],
            'null' => [null],
        ];
    }

    #[Test]
    #[DataProvider('cuitsValidos')]
    public function acepta_cuits_validos(string $cuit): void
    {
        $this->assertTrue(Cuit::esValido($cuit));
    }

    #[Test]
    #[DataProvider('cuitsInvalidos')]
    public function rechaza_cuits_invalidos(?string $cuit): void
    {
        $this->assertFalse(Cuit::esValido($cuit));
    }

    #[Test]
    public function calcula_el_digito_verificador(): void
    {
        $this->assertSame(6, Cuit::calcularDigito('2012345678'));
        $this->assertSame(9, Cuit::calcularDigito('3369345023'));
    }

    #[Test]
    public function formatea_y_normaliza(): void
    {
        $this->assertSame('20-12345678-6', Cuit::formatear('20123456786'));
        $this->assertSame('20123456786', Cuit::normalizar('20-12345678-6'));
        $this->assertSame('123', Cuit::formatear('123'));
    }

    #[Test]
    public function cuando_el_digito_da_10_pasa_al_prefijo_23_como_afip(): void
    {
        // Busco un DNI cuyo dígito con prefijo 20 dé 10 (no tiene dígito válido)
        $dni = null;
        for ($n = 10000000; $n < 10000200; $n++) {
            if (Cuit::calcularDigito('20'.$n) === null) {
                $dni = $n;
                break;
            }
        }
        $this->assertNotNull($dni, 'No se encontró un DNI de prueba');

        $cuit = Cuit::desdeDni($dni, '20');

        $this->assertStringStartsWith('23', $cuit);
        $this->assertSame('9', substr($cuit, -1));
        $this->assertTrue(Cuit::esValido($cuit));
    }

    #[Test]
    public function los_cuits_generados_desde_dni_siempre_son_validos(): void
    {
        foreach ([20_000_000, 30_123_456, 44_999_999, 12_345_678] as $dni) {
            $this->assertTrue(Cuit::esValido(Cuit::desdeDni($dni, '27')));
            $this->assertTrue(Cuit::esValido(Cuit::desdeDni($dni, '20')));
        }
    }
}
