<?php

namespace App\Http\Resources;

use App\Models\Profesional;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Estado público de una matrícula. A propósito no expone email,
 * teléfono ni domicilio: es lo mínimo para saber si el profesional está habilitado.
 *
 * @mixin Profesional
 */
class MatriculaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'matricula' => $this->matricula,
            'apellido' => $this->apellido,
            'nombre' => $this->nombre,
            'cuit' => $this->cuit_formateado,
            'profesion' => $this->profesion,
            'estado' => $this->estado->value,
            'estado_etiqueta' => $this->estado->etiqueta(),
            'habilitado' => $this->estaActiva(),
            'al_dia_con_cuotas' => $this->estaAlDia(),
            'fecha_matriculacion' => $this->fecha_matriculacion->toDateString(),
        ];
    }
}
