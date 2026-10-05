<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoTramite;
use App\Http\Controllers\Controller;
use App\Http\Resources\TramiteResource;
use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class TramiteController extends Controller
{
    /** El profesional ve sus trámites; el staff ve todos (con filtro opcional por estado). */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['estado' => ['nullable', Rule::enum(EstadoTramite::class)]]);
        $usuario = $request->user();

        $tramites = Tramite::query()
            ->with(['tipo', 'profesional'])
            ->when(! $usuario->esStaff(), fn ($q) => $q->where('profesional_id', $usuario->profesional?->id ?? 0))
            ->when($request->query('estado'), fn ($q, $estado) => $q->where('estado', $estado))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return TramiteResource::collection($tramites);
    }

    public function show(Request $request, string $numero): TramiteResource
    {
        $tramite = Tramite::where('numero', $numero)
            ->with(['tipo', 'profesional', 'movimientos'])
            ->firstOrFail();

        $this->authorize('view', $tramite);

        return new TramiteResource($tramite);
    }
}
