<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_INTENTOS = 5;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Intenta loguear. Después de 5 intentos fallidos bloquea por un minuto.
     *
     * @throws ValidationException
     */
    public function autenticar(): void
    {
        $clave = $this->claveLimite();

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            event(new Lockout($this));

            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Probá de nuevo en '.RateLimiter::availableIn($clave).' segundos.',
            ]);
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('recordarme'))) {
            RateLimiter::hit($clave);

            throw ValidationException::withMessages([
                'email' => 'El email o la contraseña no son correctos.',
            ]);
        }

        RateLimiter::clear($clave);
    }

    private function claveLimite(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
