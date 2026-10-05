<?php

namespace App\Policies;

use App\Models\Profesional;
use App\Models\User;

class ProfesionalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esStaff();
    }

    public function view(User $user, Profesional $profesional): bool
    {
        return $user->esStaff() || $profesional->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->esStaff();
    }

    public function update(User $user, Profesional $profesional): bool
    {
        return $user->esStaff();
    }

    /** Suspender o dar de baja una matrícula es decisión de administración. */
    public function cambiarEstado(User $user, Profesional $profesional): bool
    {
        return $user->esAdmin();
    }
}
