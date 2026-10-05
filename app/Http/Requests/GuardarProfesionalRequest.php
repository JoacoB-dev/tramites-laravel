<?php

namespace App\Http\Requests;

use App\Models\Profesional;
use App\Rules\CuitValido;
use App\Support\Cuit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Alta y edición de profesionales desde el panel. */
class GuardarProfesionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profesional = $this->route('profesional');

        return $profesional instanceof Profesional
            ? $this->user()->can('update', $profesional)
            : $this->user()->can('create', Profesional::class);
    }

    /** El CUIT puede venir con guiones o espacios; se valida y guarda solo con dígitos. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cuit' => Cuit::normalizar($this->input('cuit')),
            'matricula' => strtoupper(trim((string) $this->input('matricula'))),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('profesional')?->id;

        return [
            'matricula' => ['required', 'string', 'max:20', 'regex:/^[A-Z]{1,3}-\d{3,6}$/', Rule::unique('profesionales')->ignore($id)],
            'apellido' => ['required', 'string', 'max:80'],
            'nombre' => ['required', 'string', 'max:80'],
            'cuit' => ['required', new CuitValido, Rule::unique('profesionales')->ignore($id)],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'domicilio' => ['required', 'string', 'max:150'],
            'localidad' => ['required', 'string', 'max:80'],
            'profesion' => ['required', 'string', 'max:100'],
            'fecha_matriculacion' => ['required', 'date', 'before_or_equal:today'],
            // Solo en el alta: crear usuario para el portal
            'crear_acceso' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'matricula' => 'matrícula',
            'cuit' => 'CUIT',
            'telefono' => 'teléfono',
            'profesion' => 'profesión',
            'fecha_matriculacion' => 'fecha de matriculación',
        ];
    }

    public function messages(): array
    {
        return ['matricula.regex' => 'La matrícula tiene que tener el formato LETRAS-NÚMERO (ej. IC-1234).'];
    }
}
