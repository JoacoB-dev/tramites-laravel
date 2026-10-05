<?php

namespace App\Models;

use App\Enums\EstadoCuota;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['profesional_id', 'periodo', 'importe', 'vencimiento', 'pagada_en', 'medio_pago', 'registrada_por'])]
class Cuota extends Model
{
    use HasFactory;

    public const MEDIOS_PAGO = [
        'efectivo' => 'Efectivo en sede',
        'transferencia' => 'Transferencia bancaria',
        'tarjeta' => 'Tarjeta de débito/crédito',
        'debito_automatico' => 'Débito automático',
    ];

    protected function casts(): array
    {
        return [
            'importe' => 'decimal:2',
            'vencimiento' => 'date',
            'pagada_en' => 'datetime',
        ];
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class);
    }

    public function registradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrada_por');
    }

    public function estado(): EstadoCuota
    {
        if ($this->pagada_en !== null) {
            return EstadoCuota::Pagada;
        }

        return $this->vencimiento->lt(today()) ? EstadoCuota::Vencida : EstadoCuota::Pendiente;
    }

    public function estaPagada(): bool
    {
        return $this->pagada_en !== null;
    }

    /** "2026-09" => "septiembre 2026" */
    public function periodoLegible(): string
    {
        return CarbonImmutable::createFromFormat('!Y-m', $this->periodo)->translatedFormat('F Y');
    }

    #[Scope]
    protected function pagadas(Builder $query): void
    {
        $query->whereNotNull('pagada_en');
    }

    #[Scope]
    protected function impagas(Builder $query): void
    {
        $query->whereNull('pagada_en');
    }

    /** Impagas con vencimiento anterior a hoy. */
    #[Scope]
    protected function vencidas(Builder $query): void
    {
        $query->whereNull('pagada_en')->whereDate('vencimiento', '<', today());
    }

    /** Impagas que todavía no vencieron. */
    #[Scope]
    protected function pendientes(Builder $query): void
    {
        $query->whereNull('pagada_en')->whereDate('vencimiento', '>=', today());
    }

    /** Filtra por EstadoCuota (pagada / pendiente / vencida). */
    #[Scope]
    protected function conEstado(Builder $query, ?string $estado): void
    {
        match ($estado) {
            EstadoCuota::Pagada->value => $query->pagadas(),
            EstadoCuota::Pendiente->value => $query->pendientes(),
            EstadoCuota::Vencida->value => $query->vencidas(),
            default => null,
        };
    }
}
