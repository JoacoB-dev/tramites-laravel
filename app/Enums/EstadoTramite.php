<?php

namespace App\Enums;

/**
 * Estados del flujo de un trámite.
 *
 *   Iniciado ──► En revisión ──► Aprobado
 *                   │   ▲    └─► Rechazado
 *                   ▼   │
 *                 Observado
 */
enum EstadoTramite: string
{
    case Iniciado = 'iniciado';
    case EnRevision = 'en_revision';
    case Observado = 'observado';
    case Aprobado = 'aprobado';
    case Rechazado = 'rechazado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Iniciado => 'Iniciado',
            self::EnRevision => 'En revisión',
            self::Observado => 'Observado',
            self::Aprobado => 'Aprobado',
            self::Rechazado => 'Rechazado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Iniciado => 'primary',
            self::EnRevision => 'info',
            self::Observado => 'warning',
            self::Aprobado => 'success',
            self::Rechazado => 'danger',
        };
    }

    /** @return list<self> estados a los que se puede pasar desde este. */
    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::Iniciado => [self::EnRevision],
            self::EnRevision => [self::Observado, self::Aprobado, self::Rechazado],
            self::Observado => [self::EnRevision],
            self::Aprobado, self::Rechazado => [],
        };
    }

    public function puedePasarA(self $nuevo): bool
    {
        return in_array($nuevo, $this->transicionesPermitidas(), true);
    }

    public function esFinal(): bool
    {
        return $this->transicionesPermitidas() === [];
    }

    /** Observar o rechazar sin explicar el motivo no le sirve al profesional. */
    public function requiereObservacion(): bool
    {
        return $this === self::Observado || $this === self::Rechazado;
    }

    /** @return list<self> estados "abiertos" (todavía requieren trabajo de alguien). */
    public static function abiertos(): array
    {
        return [self::Iniciado, self::EnRevision, self::Observado];
    }

    /** @return array<string, string> */
    public static function opciones(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $e) => [$e->value => $e->etiqueta()])->all();
    }
}
