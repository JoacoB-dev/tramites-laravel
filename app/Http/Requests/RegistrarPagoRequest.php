<?php

namespace App\Http\Requests;

use App\Models\Cuota;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('registrarPago', $this->route('cuota'));
    }

    public function rules(): array
    {
        return [
            'medio_pago' => ['required', Rule::in(array_keys(Cuota::MEDIOS_PAGO))],
            'fecha_pago' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function attributes(): array
    {
        return ['medio_pago' => 'medio de pago', 'fecha_pago' => 'fecha de pago'];
    }
}
