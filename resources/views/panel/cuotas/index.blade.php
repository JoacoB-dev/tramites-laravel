@extends('layouts.app')

@section('titulo', 'Cuotas')

@section('contenido')
<h1 class="h3 mb-3">Cuotas de matrícula</h1>

<form method="GET" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small" for="q">Profesional</label>
            <input type="search" name="q" id="q" value="{{ $filtros['q'] ?? '' }}" class="form-control" placeholder="Matrícula, apellido o CUIT">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small" for="estado">Estado</label>
            <select name="estado" id="estado" class="form-select js-autosubmit">
                <option value="">Todas</option>
                @foreach (App\Enums\EstadoCuota::cases() as $e)
                    <option value="{{ $e->value }}" @selected(($filtros['estado'] ?? '') === $e->value)>{{ $e->etiqueta() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small" for="periodo">Período</label>
            <input type="month" name="periodo" id="periodo" value="{{ $filtros['periodo'] ?? '' }}" class="form-control">
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button></div>
    </div>
</form>

<div class="table-responsive">
    <table class="table bg-white shadow-sm align-middle" id="tabla-cuotas">
        <thead class="table-light"><tr><th>Profesional</th><th>Período</th><th>Vence</th><th class="text-end">Importe</th><th>Estado</th><th>Pago</th><th></th></tr></thead>
        <tbody>
        @forelse ($cuotas as $cuota)
            <tr data-cuota-id="{{ $cuota->id }}">
                <td>{{ $cuota->profesional->nombre_completo }}<br><span class="small text-muted">{{ $cuota->profesional->matricula }}</span></td>
                <td>{{ ucfirst($cuota->periodoLegible()) }}</td>
                <td>{{ $cuota->vencimiento->format('d/m/Y') }}</td>
                <td class="text-end">@pesos($cuota->importe)</td>
                <td class="js-estado"><x-badge :estado="$cuota->estado()" /></td>
                <td class="js-pago small">
                    @if ($cuota->estaPagada())
                        {{ $cuota->pagada_en->format('d/m/Y') }} · {{ App\Models\Cuota::MEDIOS_PAGO[$cuota->medio_pago] ?? $cuota->medio_pago }}
                    @else — @endif
                </td>
                <td class="text-end js-accion">
                    @can('registrarPago', $cuota)
                        <button type="button" class="btn btn-sm btn-outline-success js-registrar-pago"
                                data-url="{{ route('panel.cuotas.pago', $cuota) }}"
                                data-descripcion="{{ $cuota->profesional->nombre_completo }} · {{ ucfirst($cuota->periodoLegible()) }} · @pesos($cuota->importe)">
                            <i class="bi bi-cash-coin"></i> Registrar pago
                        </button>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No hay cuotas con esos filtros.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $cuotas->links() }}

{{-- Modal de pago: lo abre y envía resources/js/cuotas.js con $.ajax --}}
<div class="modal fade" id="modal-pago" tabindex="-1" aria-labelledby="modal-pago-titulo" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="form-pago" method="POST">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title h5" id="modal-pago-titulo">Registrar pago</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted" id="pago-descripcion"></p>
                <div class="mb-3">
                    <label for="medio_pago" class="form-label">Medio de pago</label>
                    <select name="medio_pago" id="medio_pago" class="form-select" required>
                        @foreach ($mediosPago as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-1">
                    <label for="fecha_pago" class="form-label">Fecha de pago</label>
                    <input type="date" name="fecha_pago" id="fecha_pago" class="form-control" max="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}">
                </div>
                <div class="alert alert-danger d-none mt-3 mb-0 small" id="pago-error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-success" id="pago-confirmar">Confirmar pago</button>
            </div>
        </form>
    </div>
</div>
@endsection
