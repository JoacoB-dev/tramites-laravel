<?php

namespace App\Models;

use App\Enums\EstadoTramite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un cambio de estado en el historial de un trámite. */
#[Fillable(['tramite_id', 'user_id', 'estado_anterior', 'estado_nuevo', 'observacion'])]
class TramiteMovimiento extends Model
{
    // El historial no se modifica: solo tiene created_at
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'estado_anterior' => EstadoTramite::class,
            'estado_nuevo' => EstadoTramite::class,
            'created_at' => 'datetime',
        ];
    }

    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
