<?php

namespace App\Policies;

use App\Models\TramiteAdjunto;
use App\Models\User;

class TramiteAdjuntoPolicy
{
    /** Puede bajar el adjunto quien puede ver el trámite. */
    public function descargar(User $user, TramiteAdjunto $adjunto): bool
    {
        return $user->can('view', $adjunto->tramite);
    }
}
