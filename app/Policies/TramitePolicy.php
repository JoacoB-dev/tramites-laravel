<?php

namespace App\Policies;

use App\Enums\EstadoTramite;
use App\Models\Tramite;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TramitePolicy
{
    /** El staff ve todo; el profesional solo sus trámites. */
    public function view(User $user, Tramite $tramite): bool
    {
        return $user->esStaff() || $this->esDelProfesional($user, $tramite);
    }

    public function create(User $user): Response
    {
        $profesional = $user->profesional;

        if (! $user->esProfesional() || $profesional === null) {
            return Response::deny('Solo los profesionales matriculados pueden iniciar trámites.');
        }

        return $profesional->estaActiva()
            ? Response::allow()
            : Response::deny('Tu matrícula está '.mb_strtolower($profesional->estado->etiqueta()).': no podés iniciar trámites.');
    }

    public function responder(User $user, Tramite $tramite): bool
    {
        return $this->esDelProfesional($user, $tramite) && $tramite->estado === EstadoTramite::Observado;
    }

    public function cambiarEstado(User $user, Tramite $tramite): bool
    {
        return $user->esStaff() && ! $tramite->estado->esFinal();
    }

    private function esDelProfesional(User $user, Tramite $tramite): bool
    {
        return $user->esProfesional()
            && $user->profesional !== null
            && $tramite->profesional_id === $user->profesional->id;
    }
}
