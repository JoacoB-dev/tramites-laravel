@extends('layouts.app')

@section('titulo', $profesional->nombre_completo)

@section('contenido')
<a href="{{ route('panel.profesionales.index') }}" class="small"><i class="bi bi-arrow-left"></i> Profesionales</a>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-3">
    <h1 class="h3 mb-0">{{ $profesional->nombre_completo }} <x-badge :estado="$profesional->estado" class="fs-6 align-middle" /></h1>
    @can('update', $profesional)
        <a href="{{ route('panel.profesionales.edit', $profesional) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
    @endcan
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm mb-4"><div class="card-body">
            <dl class="row small mb-0">
                <dt class="col-5">Matrícula</dt><dd class="col-7">{{ $profesional->matricula }}</dd>
                <dt class="col-5">CUIT</dt><dd class="col-7 font-monospace">{{ $profesional->cuit_formateado }}</dd>
                <dt class="col-5">Profesión</dt><dd class="col-7">{{ $profesional->profesion }}</dd>
                <dt class="col-5">Email</dt><dd class="col-7">{{ $profesional->email }}</dd>
                <dt class="col-5">Teléfono</dt><dd class="col-7">{{ $profesional->telefono ?? '—' }}</dd>
                <dt class="col-5">Domicilio</dt><dd class="col-7">{{ $profesional->domicilio }}, {{ $profesional->localidad }}</dd>
                <dt class="col-5">Matriculación</dt><dd class="col-7">{{ $profesional->fecha_matriculacion->format('d/m/Y') }}</dd>
                <dt class="col-5">Acceso al portal</dt><dd class="col-7">{{ $profesional->user ? 'Sí ('.$profesional->user->email.')' : 'No' }}</dd>
                @if ($profesional->motivo_estado)
                    <dt class="col-5">Motivo estado</dt><dd class="col-7">{{ $profesional->motivo_estado }}</dd>
                @endif
            </dl>
        </div></div>

        @can('cambiarEstado', $profesional)
            <div class="card shadow-sm border-warning mb-4"><div class="card-body">
                <h2 class="h6">Estado de la matrícula <span class="badge text-bg-dark">solo admin</span></h2>
                <form method="POST" action="{{ route('panel.profesionales.estado', $profesional) }}" class="js-confirmar" data-confirmar="¿Confirmás el cambio de estado de la matrícula {{ $profesional->matricula }}?">
                    @csrf @method('PATCH')
                    <div class="row g-2">
                        <div class="col-sm-5">
                            <select name="estado" class="form-select @error('estado') is-invalid @enderror">
                                @foreach (App\Enums\EstadoMatricula::opciones() as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected($profesional->estado->value === $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-7">
                            <input type="text" name="motivo" class="form-control @error('motivo') is-invalid @enderror" placeholder="Motivo (obligatorio si no es Activa)" value="{{ old('motivo') }}">
                            @error('motivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <button class="btn btn-warning btn-sm mt-2">Cambiar estado</button>
                </form>
            </div></div>
        @endcan

        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h6 text-uppercase text-muted">Certificados</h2>
            @forelse ($certificados as $c)
                <div class="border-bottom py-2 small d-flex justify-content-between align-items-center gap-2">
                    <span><code>{{ $c->codigo }}</code> · {{ $c->emitido_en->format('d/m/Y') }}
                        @if ($c->estaAnulado()) <span class="badge text-bg-danger">Anulado</span>
                        @elseif ($c->estaVencido()) <span class="badge text-bg-secondary">Vencido</span>
                        @else <span class="badge text-bg-success">Vigente</span>@endif
                    </span>
                    <span class="d-flex gap-1">
                        <a href="{{ route('certificados.descargar', $c) }}" class="btn btn-sm btn-outline-secondary" title="PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                        @can('anular', $c)
                            <form method="POST" action="{{ route('panel.certificados.anular', $c) }}" class="js-anular d-flex gap-1">
                                @csrf
                                <input type="hidden" name="motivo_anulacion">
                                <button class="btn btn-sm btn-outline-danger" title="Anular"><i class="bi bi-x-circle"></i></button>
                            </form>
                        @endcan
                    </span>
                </div>
            @empty
                <p class="small text-muted mb-0">Sin certificados emitidos.</p>
            @endforelse
            @error('motivo_anulacion')<div class="text-danger small">{{ $message }}</div>@enderror
        </div></div>
    </div>
    <div class="col-lg-7">
        <h2 class="h5">Trámites recientes</h2>
        @include('panel.tramites._lista', ['tramites' => $tramites->each->setRelation('profesional', $profesional), 'vacio' => 'Sin trámites.'])

        <h2 class="h5 mt-4">Cuotas (últimos 12 períodos)</h2>
        <table class="table table-sm bg-white shadow-sm">
            <thead class="table-light"><tr><th>Período</th><th>Vence</th><th class="text-end">Importe</th><th>Estado</th></tr></thead>
            <tbody>
            @forelse ($cuotas as $cuota)
                <tr>
                    <td>{{ ucfirst($cuota->periodoLegible()) }}</td>
                    <td>{{ $cuota->vencimiento->format('d/m/Y') }}</td>
                    <td class="text-end">@pesos($cuota->importe)</td>
                    <td><x-badge :estado="$cuota->estado()" /></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">Sin cuotas.</td></tr>
            @endforelse
            </tbody>
        </table>
        <a href="{{ route('panel.cuotas.index', ['q' => $profesional->matricula]) }}" class="small">Gestionar cuotas de este profesional</a>
    </div>
</div>
@endsection
