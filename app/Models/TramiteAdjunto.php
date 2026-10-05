<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

#[Fillable(['tramite_id', 'user_id', 'nombre_original', 'ruta', 'tamanio'])]
class TramiteAdjunto extends Model
{
    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tamanioLegible(): string
    {
        return Number::fileSize($this->tamanio, precision: 1);
    }
}
