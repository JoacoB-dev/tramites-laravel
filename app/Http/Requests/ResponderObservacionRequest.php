<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResponderObservacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('responder', $this->route('tramite'));
    }

    public function rules(): array
    {
        return [
            'comentario' => ['required', 'string', 'min:10', 'max:2000'],
            'adjuntos' => ['nullable', 'array', 'max:'.config('consejo.adjuntos.max_archivos')],
            'adjuntos.*' => ['file', 'mimes:pdf', 'max:'.config('consejo.adjuntos.max_kb')],
        ];
    }

    public function messages(): array
    {
        return ['adjuntos.*.mimes' => 'Los adjuntos tienen que ser archivos PDF.'];
    }
}
