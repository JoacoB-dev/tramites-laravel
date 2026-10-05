@extends('layouts.app')

@section('titulo', 'Profesionales')

@section('contenido')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Profesionales matriculados</h1>
    @can('create', App\Models\Profesional::class)
        <a href="{{ route('panel.profesionales.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nueva matrícula</a>
    @endcan
</div>

<form method="GET" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ $filtros['q'] ?? '' }}" class="form-control" placeholder="Matrícula, apellido, nombre o CUIT">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small" for="estado">Estado</label>
            <select name="estado" id="estado" class="form-select js-autosubmit">
                <option value="">Todos</option>
                @foreach (App\Enums\EstadoMatricula::opciones() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small" for="orden">Ordenar por</label>
            <select name="orden" id="orden" class="form-select js-autosubmit">
                <option value="apellido">Apellido</option>
                <option value="matricula" @selected(($filtros['orden'] ?? '') === 'matricula')>Matrícula</option>
                <option value="fecha_matriculacion" @selected(($filtros['orden'] ?? '') === 'fecha_matriculacion')>Antigüedad</option>
            </select>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button></div>
    </div>
</form>

<p class="small text-muted mb-2">{{ $profesionales->total() }} profesional(es)</p>
<div class="table-responsive">
    <table class="table table-hover bg-white shadow-sm align-middle">
        <thead class="table-light"><tr><th>Matrícula</th><th>Apellido y nombre</th><th>CUIT</th><th>Profesión</th><th>Estado</th><th>Cuotas</th><th></th></tr></thead>
        <tbody>
        @forelse ($profesionales as $p)
            <tr>
                <td class="fw-semibold">{{ $p->matricula }}</td>
                <td>{{ $p->nombre_completo }}</td>
                <td class="font-monospace small">{{ $p->cuit_formateado }}</td>
                <td class="small">{{ $p->profesion }}</td>
                <td><x-badge :estado="$p->estado" /></td>
                <td>
                    @if ($p->cuotas_vencidas_count > 0)
                        <span class="badge text-bg-danger">{{ $p->cuotas_vencidas_count }} vencida(s)</span>
                    @else
                        <span class="badge text-bg-light border">Al día</span>
                    @endif
                </td>
                <td class="text-end"><a href="{{ route('panel.profesionales.show', $p) }}" class="btn btn-sm btn-outline-primary">Ficha</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron profesionales.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $profesionales->links() }}
@endsection
