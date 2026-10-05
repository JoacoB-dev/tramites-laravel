<?php

namespace App\Http\Resources;

use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tramite */
class TramiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'numero' => $this->numero,
            'tipo' => $this->whenLoaded('tipo', fn () => [
                'codigo' => $this->tipo->codigo,
                'nombre' => $this->tipo->nombre,
            ]),
            'matricula' => $this->whenLoaded('profesional', fn () => $this->profesional->matricula),
            'estado' => $this->estado->value,
            'estado_etiqueta' => $this->estado->etiqueta(),
            'iniciado_en' => $this->created_at?->toIso8601String(),
            'resuelto_en' => $this->resuelto_en?->toIso8601String(),
            'historial' => $this->whenLoaded('movimientos', fn () => $this->movimientos->map(fn ($m) => [
                'estado' => $m->estado_nuevo->value,
                'observacion' => $m->observacion,
                'fecha' => $m->created_at->toIso8601String(),
            ])),
        ];
    }
}
