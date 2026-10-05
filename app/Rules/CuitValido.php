<?php

namespace App\Rules;

use App\Support\Cuit;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CuitValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Cuit::esValido($value)) {
            $fail('El :attribute no es válido (revisá los 11 dígitos y el dígito verificador).');
        }
    }
}
