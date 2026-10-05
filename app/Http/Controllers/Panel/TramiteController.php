<?php

namespace App\Http\Controllers\Panel;

use App\Enums\EstadoTramite;
use App\Exceptions\OperacionNoPermitida;
use App\Http\Controllers\Controller;
use App\Http\Requests\CambiarEstadoTramiteRequest;
use App\Models\TipoTramite;
use App\Models\Tramite;
use App\Models\User;
use App\Services\GestorTramites;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TramiteController extends Controller
{
    public function __construct(private readonly GestorTramites $gestor) {}

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::enum(EstadoTramite::class)],
            'tipo' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'empleado_id' => ['nullable', 'integer'],
        ]);

        $tramites = Tramite::query()
            ->with(['tipo', 'profesional', 'empleado'])
            ->filtrar($filtros)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('panel.tramites.index', [
            'tramites' => $tramites,
            'filtros' => $filtros,
            'tipos' => TipoTramite::orderBy('nombre')->get(),
            'empleados' => User::whereIn('rol', ['empleado', 'admin'])->orderBy('name')->get(),
        ]);
    }

    public function show(Tramite $tramite): View
    {
        $this->authorize('view', $tramite);

        $tramite->load(['tipo', 'profesional', 'empleado', 'movimientos.user', 'adjuntos', 'certificado']);

        return view('panel.tramites.show', [
            'tramite' => $tramite,
            'alDia' => $tramite->profesional->estaAlDia(),
        ]);
    }

    public function cambiarEstado(CambiarEstadoTramiteRequest $request, Tramite $tramite): RedirectResponse
    {
        try {
            $tramite = $this->gestor->cambiarEstado(
                $tramite,
                $request->estadoNuevo(),
                $request->user(),
                $request->validated('observacion'),
            );
        } catch (OperacionNoPermitida $e) {
            return back()->withInput()->withErrors(['estado' => $e->getMessage()]);
        }

        return redirect()
            ->route('panel.tramites.show', $tramite)
            ->with('ok', "El trámite {$tramite->numero} pasó a \"{$tramite->estado->etiqueta()}\". Se avisó al profesional por mail.");
    }
}
