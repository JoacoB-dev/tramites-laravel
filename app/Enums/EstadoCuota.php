<?php

namespace App\Enums;

/** Estado calculado de una cuota (no se guarda: depende de la fecha de hoy). */
enum EstadoCuota: string
{
    case Pagada = 'pagada';
    case Pendiente = 'pendiente';
    case Vencida = 'vencida';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pagada => 'Pagada',
            self::Pendiente => 'Pendiente',
            self::Vencida => 'Vencida',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pagada => 'success',
            self::Pendiente => 'secondary',
            self::Vencida => 'danger',
        };
    }
}
