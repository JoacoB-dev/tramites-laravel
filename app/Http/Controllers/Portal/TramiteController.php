<?php

namespace App\Http\Controllers\Portal;

use App\Exceptions\OperacionNoPermitida;
use App\Http\Controllers\Controller;
use App\Http\Requests\IniciarTramiteRequest;
use App\Http\Requests\ResponderObservacionRequest;
use App\Models\TipoTramite;
use App\Models\Tramite;
use App\Services\GestorTramites;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TramiteController extends Controller
{
    public function __construct(private readonly GestorTramites $gestor) {}

    public function index(Request $request): View
    {
        $tramites = $request->user()->profesional->tramites()
            ->with('tipo')
            ->latest()
            ->paginate(10);

        return view('portal.tramites.index', compact('tramites'));
    }

    public function create(): View
    {
        $this->authorize('create', Tramite::class);

        return view('portal.tramites.create', [
            'tipos' => TipoTramite::activos()->get(),
        ]);
    }

    public function store(IniciarTramiteRequest $request): RedirectResponse
    {
        try {
            $tramite = $this->gestor->iniciar(
                profesional: $request->user()->profesional,
                tipo: $request->tipoElegido(),
                autor: $request->user(),
                descripcion: $request->validated('descripcion'),
                datos: $request->datosDelTipo(),
                archivos: $request->file('adjuntos', []),
            );
        } catch (OperacionNoPermitida $e) {
            return back()->withInput()->withErrors(['tipo_tramite_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('portal.tramites.show', $tramite)
            ->with('ok', "Iniciaste el trámite {$tramite->numero}. Te vamos a avisar por mail cuando cambie de estado.");
    }

    public function show(Tramite $tramite): View
    {
        $this->authorize('view', $tramite);

        $tramite->load(['tipo', 'movimientos.user', 'adjuntos', 'certificado']);

        return view('portal.tramites.show', compact('tramite'));
    }

    public function responder(ResponderObservacionRequest $request, Tramite $tramite): RedirectResponse
    {
        try {
            $this->gestor->responderObservacion(
                $tramite,
                $request->user(),
                $request->validated('comentario'),
                $request->file('adjuntos', []),
            );
        } catch (OperacionNoPermitida $e) {
            return back()->withErrors(['comentario' => $e->getMessage()]);
        }

        return redirect()
            ->route('portal.tramites.show', $tramite)
            ->with('ok', 'Enviaste tu respuesta. El trámite volvió a revisión.');
    }
}
