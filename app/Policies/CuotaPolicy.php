<?php

namespace App\Policies;

use App\Models\Cuota;
use App\Models\User;

class CuotaPolicy
{
    public function registrarPago(User $user, Cuota $cuota): bool
    {
        return $user->esStaff() && ! $cuota->estaPagada();
    }
}
