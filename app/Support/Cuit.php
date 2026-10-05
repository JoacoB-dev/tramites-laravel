<?php

namespace App\Support;

/**
 * Validación y formato de CUIT/CUIL argentinos (módulo 11).
 *
 * El CUIT tiene 11 dígitos: prefijo (2) + documento (8) + dígito verificador (1).
 * El verificador se calcula multiplicando los 10 primeros dígitos por
 * 5,4,3,2,7,6,5,4,3,2, sumando, y haciendo 11 - (suma % 11).
 * Si da 11 el dígito es 0; si da 10 la combinación no es válida
 * (en ese caso AFIP asigna prefijo 23 o 33).
 */
final class Cuit
{
    private const PESOS = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    /** Prefijos usados: 20/23/24/27 personas humanas, 30/33/34 personas jurídicas. */
    private const PREFIJOS = ['20', '23', '24', '27', '30', '33', '34'];

    /** Deja solo los dígitos: "20-12345678-6" => "20123456786". */
    public static function normalizar(?string $cuit): string
    {
        return preg_replace('/\D/', '', (string) $cuit) ?? '';
    }

    public static function esValido(?string $cuit): bool
    {
        $numero = self::normalizar($cuit);

        if (strlen($numero) !== 11) {
            return false;
        }

        if (! in_array(substr($numero, 0, 2), self::PREFIJOS, true)) {
            return false;
        }

        return self::calcularDigito(substr($numero, 0, 10)) === (int) $numero[10];
    }

    /**
     * Calcula el dígito verificador para los 10 primeros dígitos.
     * Devuelve null si la combinación no admite dígito (resultado 10).
     */
    public static function calcularDigito(string $base): ?int
    {
        if (! preg_match('/^\d{10}$/', $base)) {
            throw new \InvalidArgumentException('La base del CUIT debe tener 10 dígitos.');
        }

        $suma = 0;
        foreach (self::PESOS as $i => $peso) {
            $suma += (int) $base[$i] * $peso;
        }

        $resultado = 11 - ($suma % 11);

        return match ($resultado) {
            11 => 0,
            10 => null,
            default => $resultado,
        };
    }

    /** "20123456786" => "20-12345678-6". Si no tiene 11 dígitos lo devuelve tal cual. */
    public static function formatear(?string $cuit): string
    {
        $numero = self::normalizar($cuit);

        if (strlen($numero) !== 11) {
            return (string) $cuit;
        }

        return substr($numero, 0, 2).'-'.substr($numero, 2, 8).'-'.$numero[10];
    }

    /**
     * Arma un CUIT válido a partir de un DNI (lo usan las factories y los seeders).
     * Si con el prefijo pedido el dígito da 10, pasa a 23 (o 33) como hace AFIP.
     */
    public static function desdeDni(int|string $dni, string $prefijo = '20'): string
    {
        $dni = str_pad((string) $dni, 8, '0', STR_PAD_LEFT);
        $digito = self::calcularDigito($prefijo.$dni);

        if ($digito === null) {
            // Personas humanas pasan a 23 y jurídicas a 33 (criterio de AFIP).
            $prefijo = in_array($prefijo, ['30', '33', '34'], true) ? '33' : '23';
            $digito = self::calcularDigito($prefijo.$dni);
        }

        return $prefijo.$dni.$digito;
    }
}
