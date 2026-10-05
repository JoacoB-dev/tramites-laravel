<?php

namespace App\Enums;

enum EstadoMatricula: string
{
    case Activa = 'activa';
    case Suspendida = 'suspendida';
    case Baja = 'baja';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Activa => 'Activa',
            self::Suspendida => 'Suspendida',
            self::Baja => 'Baja',
        };
    }

    /** Clase de color de Bootstrap para el badge. */
    public function color(): string
    {
        return match ($this) {
            self::Activa => 'success',
            self::Suspendida => 'warning',
            self::Baja => 'secondary',
        };
    }

    /** @return array<string, string> valor => etiqueta, para selects. */
    public static function opciones(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $e) => [$e->value => $e->etiqueta()])->all();
    }
}
