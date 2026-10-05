<?php

namespace App\Http\Controllers\Panel;

use App\Enums\EstadoCuota;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarPagoRequest;
use App\Models\Cuota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CuotaController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::enum(EstadoCuota::class)],
            'periodo' => ['nullable', 'date_format:Y-m'],
        ]);

        $cuotas = Cuota::query()
            ->with('profesional')
            ->conEstado($filtros['estado'] ?? null)
            ->when($filtros['periodo'] ?? null, fn ($q, $periodo) => $q->where('periodo', $periodo))
            ->when($filtros['q'] ?? null, fn ($q, $texto) => $q->whereHas('profesional', fn ($p) => $p->buscar($texto)))
            ->orderByDesc('periodo')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('panel.cuotas.index', [
            'cuotas' => $cuotas,
            'filtros' => $filtros,
            'mediosPago' => Cuota::MEDIOS_PAGO,
        ]);
    }

    /**
     * Registra el pago. Lo llama el modal con jQuery ($.ajax, espera JSON);
     * si JS está deshabilitado, el form normal hace POST y vuelve con un mensaje.
     */
    public function registrarPago(RegistrarPagoRequest $request, Cuota $cuota): JsonResponse|RedirectResponse
    {
        $fecha = $request->validated('fecha_pago');

        $cuota->update([
            'pagada_en' => $fecha ? Carbon::parse($fecha)->setTimeFrom(now()) : now(),
            'medio_pago' => $request->validated('medio_pago'),
            'registrada_por' => $request->user()->id,
        ]);

        $mensaje = "Pago registrado: cuota {$cuota->periodoLegible()} de {$cuota->profesional->nombre_completo}.";

        if ($request->expectsJson()) {
            return response()->json([
                'mensaje' => $mensaje,
                'cuota' => [
                    'id' => $cuota->id,
                    'estado' => $cuota->estado()->value,
                    'estado_etiqueta' => $cuota->estado()->etiqueta(),
                    'color' => $cuota->estado()->color(),
                    'pagada_en' => $cuota->pagada_en->format('d/m/Y'),
                    'medio_pago' => Cuota::MEDIOS_PAGO[$cuota->medio_pago],
                    'importe' => Number::currency((float) $cuota->importe, 'ARS', 'es_AR'),
                ],
            ]);
        }

        return back()->with('ok', $mensaje);
    }
}
