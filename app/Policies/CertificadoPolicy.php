<?php

namespace App\Policies;

use App\Models\Certificado;
use App\Models\User;

class CertificadoPolicy
{
    public function descargar(User $user, Certificado $certificado): bool
    {
        return $user->esStaff() || $certificado->profesional->user_id === $user->id;
    }

    public function anular(User $user, Certificado $certificado): bool
    {
        return $user->esAdmin() && ! $certificado->estaAnulado();
    }
}
