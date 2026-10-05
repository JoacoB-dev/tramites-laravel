<?php

namespace App\Http\Requests;

use App\Enums\EstadoMatricula;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoMatriculaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cambiarEstado', $this->route('profesional'));
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::enum(EstadoMatricula::class)],
            // Suspender o dar de baja sin motivo no queda documentado
            'motivo' => [Rule::requiredIf(fn () => $this->input('estado') !== EstadoMatricula::Activa->value), 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['motivo.required' => 'Indicá el motivo de la suspensión o baja.'];
    }
}
