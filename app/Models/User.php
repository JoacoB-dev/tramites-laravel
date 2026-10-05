<?php

namespace App\Models;

use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'rol'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => Rol::class,
        ];
    }

    /** Ficha de matriculado asociada (solo para usuarios con rol profesional). */
    public function profesional(): HasOne
    {
        return $this->hasOne(Profesional::class);
    }

    public function esStaff(): bool
    {
        return $this->rol->esStaff();
    }

    public function esAdmin(): bool
    {
        return $this->rol === Rol::Admin;
    }

    public function esProfesional(): bool
    {
        return $this->rol === Rol::Profesional;
    }

    /** Pantalla de inicio según el rol. */
    public function rutaInicio(): string
    {
        return $this->esStaff() ? route('panel.inicio') : route('portal.inicio');
    }
}
