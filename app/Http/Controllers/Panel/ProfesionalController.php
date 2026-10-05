<?php

namespace App\Http\Controllers\Panel;

use App\Enums\EstadoMatricula;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\CambiarEstadoMatriculaRequest;
use App\Http\Requests\GuardarProfesionalRequest;
use App\Models\Profesional;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfesionalController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Profesional::class);

        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::enum(EstadoMatricula::class)],
            'orden' => ['nullable', Rule::in(['apellido', 'matricula', 'fecha_matriculacion'])],
        ]);

        $profesionales = Profesional::query()
            ->buscar($filtros['q'] ?? null)
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            // Cantidad de cuotas vencidas en la misma consulta (sin N+1)
            ->withCount(['cuotas as cuotas_vencidas_count' => fn ($q) => $q->vencidas()])
            ->orderBy($filtros['orden'] ?? 'apellido')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('panel.profesionales.index', compact('profesionales', 'filtros'));
    }

    public function create(): View
    {
        $this->authorize('create', Profesional::class);

        return view('panel.profesionales.form', ['profesional' => new Profesional]);
    }

    public function store(GuardarProfesionalRequest $request): RedirectResponse
    {
        $datos = $request->safe()->except('crear_acceso');
        $passwordTemporal = null;

        $profesional = DB::transaction(function () use ($request, $datos, &$passwordTemporal) {
            $profesional = Profesional::create($datos + ['estado' => EstadoMatricula::Activa]);

            if ($request->boolean('crear_acceso')) {
                $passwordTemporal = Str::password(12, symbols: false);
                $usuario = User::create([
                    'name' => "{$profesional->nombre} {$profesional->apellido}",
                    'email' => $profesional->email,
                    'password' => $passwordTemporal,
                    'rol' => Rol::Profesional,
                ]);
                $profesional->update(['user_id' => $usuario->id]);
            }

            return $profesional;
        });

        $mensaje = "Profesional {$profesional->nombre_completo} matriculado/a con la matrícula {$profesional->matricula}.";
        if ($passwordTemporal) {
            // En un sistema real se mandaría un link para elegir contraseña; acá se muestra una sola vez
            $mensaje .= " Acceso al portal: {$profesional->email} / contraseña temporal {$passwordTemporal}";
        }

        return redirect()->route('panel.profesionales.show', $profesional)->with('ok', $mensaje);
    }

    public function show(Profesional $profesional): View
    {
        $this->authorize('view', $profesional);

        return view('panel.profesionales.show', [
            'profesional' => $profesional->load('user'),
            'tramites' => $profesional->tramites()->with('tipo')->latest()->limit(10)->get(),
            'cuotas' => $profesional->cuotas()->orderByDesc('periodo')->limit(12)->get(),
            'certificados' => $profesional->certificados()->latest('emitido_en')->get(),
        ]);
    }

    public function edit(Profesional $profesional): View
    {
        $this->authorize('update', $profesional);

        return view('panel.profesionales.form', compact('profesional'));
    }

    public function update(GuardarProfesionalRequest $request, Profesional $profesional): RedirectResponse
    {
        $profesional->update($request->safe()->except('crear_acceso'));

        return redirect()->route('panel.profesionales.show', $profesional)->with('ok', 'Datos actualizados.');
    }

    public function cambiarEstado(CambiarEstadoMatriculaRequest $request, Profesional $profesional): RedirectResponse
    {
        $estado = EstadoMatricula::from($request->validated('estado'));

        $profesional->update([
            'estado' => $estado,
            'motivo_estado' => $estado === EstadoMatricula::Activa ? null : $request->validated('motivo'),
        ]);

        return back()->with('ok', "La matrícula {$profesional->matricula} quedó {$estado->etiqueta()}.");
    }
}
