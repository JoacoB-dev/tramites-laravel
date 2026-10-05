<?php

namespace App\Http\Requests;

use App\Enums\EstadoTramite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoTramiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cambiarEstado', $this->route('tramite'));
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::enum(EstadoTramite::class)],
            'observacion' => [
                Rule::requiredIf(fn () => $this->estadoNuevo()?->requiereObservacion() ?? false),
                'nullable', 'string', 'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return ['observacion.required' => 'Para observar o rechazar un trámite tenés que explicar el motivo.'];
    }

    public function estadoNuevo(): ?EstadoTramite
    {
        return EstadoTramite::tryFrom((string) $this->input('estado'));
    }
}
