<?php

namespace App\Models;

use App\Enums\EstadoTramite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['numero', 'profesional_id', 'tipo_tramite_id', 'estado', 'descripcion', 'datos', 'empleado_id', 'resuelto_en'])]
class Tramite extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'estado' => EstadoTramite::class,
            'datos' => 'array',
            'resuelto_en' => 'datetime',
        ];
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class);
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoTramite::class, 'tipo_tramite_id');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'empleado_id');
    }

    /** Historial de estados, del más viejo al más nuevo. */
    public function movimientos(): HasMany
    {
        return $this->hasMany(TramiteMovimiento::class)->orderBy('created_at')->orderBy('id');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(TramiteAdjunto::class)->orderBy('id');
    }

    public function certificado(): HasOne
    {
        return $this->hasOne(Certificado::class);
    }

    /** Número legible a partir del id: TR-2026-00042. */
    public static function generarNumero(int $id, ?int $anio = null): string
    {
        return sprintf('TR-%d-%05d', $anio ?? now()->year, $id);
    }

    /**
     * Filtros del listado del panel. Recibe lo que viene en la query string
     * (ya validado en el controller) e ignora lo que esté vacío.
     *
     * @param  array<string, mixed>  $filtros
     */
    #[Scope]
    protected function filtrar(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['estado'] ?? null, fn (Builder $q, $estado) => $q->where('estado', $estado))
            ->when($filtros['tipo'] ?? null, fn (Builder $q, $tipo) => $q->where('tipo_tramite_id', $tipo))
            ->when($filtros['desde'] ?? null, fn (Builder $q, $desde) => $q->whereDate('created_at', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn (Builder $q, $hasta) => $q->whereDate('created_at', '<=', $hasta))
            ->when($filtros['empleado_id'] ?? null, fn (Builder $q, $id) => $q->where('empleado_id', $id))
            ->when(trim((string) ($filtros['q'] ?? '')), function (Builder $q, string $texto) {
                $q->where(function (Builder $sub) use ($texto) {
                    $sub->where('numero', 'like', "%{$texto}%")
                        ->orWhereHas('profesional', fn (Builder $p) => $p->buscar($texto));
                });
            });
    }

    #[Scope]
    protected function abiertos(Builder $query): void
    {
        $query->whereIn('estado', EstadoTramite::abiertos());
    }
}
