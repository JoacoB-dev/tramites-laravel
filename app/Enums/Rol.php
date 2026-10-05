<?php

namespace App\Enums;

enum Rol: string
{
    case Profesional = 'profesional';
    case Empleado = 'empleado';
    case Admin = 'admin';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Profesional => 'Profesional',
            self::Empleado => 'Empleado/a del consejo',
            self::Admin => 'Administrador/a',
        };
    }

    /** Empleados y administradores trabajan en el panel interno. */
    public function esStaff(): bool
    {
        return $this === self::Empleado || $this === self::Admin;
    }
}
