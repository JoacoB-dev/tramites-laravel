@extends('layouts.app')

@section('titulo', 'Trámites')

@section('contenido')
<h1 class="h3 mb-3">Trámites</h1>

<form method="GET" class="card card-body shadow-sm mb-3" id="filtros">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ $filtros['q'] ?? '' }}" class="form-control" placeholder="Número, matrícula, apellido o CUIT">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small" for="estado">Estado</label>
            <select name="estado" id="estado" class="form-select js-autosubmit">
                <option value="">Todos</option>
                @foreach (App\Enums\EstadoTramite::opciones() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small" for="tipo">Tipo</label>
            <select name="tipo" id="tipo" class="form-select js-autosubmit">
                <option value="">Todos</option>
                @foreach ($tipos as $tipo)
                    <option value="{{ $tipo->id }}" @selected(($filtros['tipo'] ?? '') == $tipo->id)>{{ $tipo->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small" for="empleado_id">Asignado a</label>
            <select name="empleado_id" id="empleado_id" class="form-select js-autosubmit">
                <option value="">Cualquiera</option>
                @foreach ($empleados as $empleado)
                    <option value="{{ $empleado->id }}" @selected(($filtros['empleado_id'] ?? '') == $empleado->id)>{{ $empleado->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-1">
            <label class="form-label small" for="desde">Desde</label>
            <input type="date" name="desde" id="desde" value="{{ $filtros['desde'] ?? '' }}" class="form-control form-control-sm @error('desde') is-invalid @enderror">
        </div>
        <div class="col-6 col-md-1">
            <label class="form-label small" for="hasta">Hasta</label>
            <input type="date" name="hasta" id="hasta" value="{{ $filtros['hasta'] ?? '' }}" class="form-control form-control-sm @error('hasta') is-invalid @enderror">
        </div>
        <div class="col-6 col-md-1 d-grid">
            <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
        </div>
    </div>
    @if ($errors->any())<div class="text-danger small mt-2">{{ $errors->first() }}</div>@endif
    @if (array_filter($filtros))
        <a href="{{ route('panel.tramites.index') }}" class="small mt-2">Limpiar filtros</a>
    @endif
</form>

<p class="small text-muted mb-2">{{ $tramites->total() }} trámite(s)</p>
<div class="table-responsive">
    <table class="table table-hover bg-white shadow-sm align-middle">
        <thead class="table-light">
            <tr><th>Número</th><th>Profesional</th><th>Tipo</th><th>Iniciado</th><th>Asignado</th><th>Estado</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($tramites as $tramite)
            <tr>
                <td class="fw-semibold">{{ $tramite->numero }}</td>
                <td>{{ $tramite->profesional->nombre_completo }}<br><span class="small text-muted">{{ $tramite->profesional->matricula }}</span></td>
                <td>{{ $tramite->tipo->nombre }}</td>
                <td>{{ $tramite->created_at->format('d/m/Y') }}</td>
                <td class="small">{{ $tramite->empleado?->name ?? '—' }}</td>
                <td><x-badge :estado="$tramite->estado" /></td>
                <td class="text-end"><a href="{{ route('panel.tramites.show', $tramite) }}" class="btn btn-sm btn-outline-primary">Abrir</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No hay trámites con esos filtros.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $tramites->links() }}
@endsection
