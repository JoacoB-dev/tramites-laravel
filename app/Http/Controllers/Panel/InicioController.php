<?php

namespace App\Http\Controllers\Panel;

use App\Enums\EstadoMatricula;
use App\Enums\EstadoTramite;
use App\Http\Controllers\Controller;
use App\Models\Cuota;
use App\Models\Profesional;
use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function __invoke(Request $request): View
    {
        // Un solo GROUP BY para los contadores por estado
        $porEstado = Tramite::query()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return view('panel.inicio', [
            'porEstado' => collect(EstadoTramite::cases())->mapWithKeys(
                fn (EstadoTramite $e) => [$e->value => (int) ($porEstado[$e->value] ?? 0)]
            ),
            'matriculasActivas' => Profesional::where('estado', EstadoMatricula::Activa)->count(),
            'cuotasVencidas' => Cuota::vencidas()->count(),
            'misTramites' => Tramite::abiertos()
                ->where('empleado_id', $request->user()->id)
                ->with(['tipo', 'profesional'])
                ->oldest()
                ->limit(8)
                ->get(),
            'sinAsignar' => Tramite::where('estado', EstadoTramite::Iniciado)
                ->whereNull('empleado_id')
                ->with(['tipo', 'profesional'])
                ->oldest()
                ->limit(8)
                ->get(),
        ]);
    }
}
