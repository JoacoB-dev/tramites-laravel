@extends('layouts.app')

@section('titulo', 'Mi matrícula')

@section('contenido')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Hola, {{ $profesional->nombre }}</h1>
    @can('create', App\Models\Tramite::class)
        <a href="{{ route('portal.tramites.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Iniciar trámite</a>
    @endcan
</div>

@unless ($profesional->estaActiva())
    <div class="alert alert-warning">
        Tu matrícula está <strong>{{ mb_strtolower($profesional->estado->etiqueta()) }}</strong>
        @if ($profesional->motivo_estado) ({{ $profesional->motivo_estado }}) @endif.
        Mientras tanto no podés iniciar trámites. Comunicate con el consejo.
    </div>
@endunless

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted">Matrícula</h2>
                <p class="display-6 mb-1">{{ $profesional->matricula }}</p>
                <x-badge :estado="$profesional->estado" class="mb-3" />
                <dl class="small mb-0">
                    <dt>Profesión</dt><dd>{{ $profesional->profesion }}</dd>
                    <dt>CUIT</dt><dd>{{ $profesional->cuit_formateado }}</dd>
                    <dt>Domicilio</dt><dd>{{ $profesional->domicilio }}, {{ $profesional->localidad }}</dd>
                    <dt>Matriculado/a desde</dt><dd class="mb-0">{{ $profesional->fecha_matriculacion->format('d/m/Y') }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted">Cuotas impagas</h2>
                @forelse ($cuotasImpagas as $cuota)
                    <div class="d-flex justify-content-between border-bottom py-1 small">
                        <span>{{ ucfirst($cuota->periodoLegible()) }} <x-badge :estado="$cuota->estado()" /></span>
                        <span>@pesos($cuota->importe)</span>
                    </div>
                @empty
                    <p class="text-success mb-0"><i class="bi bi-check-circle"></i> Estás al día.</p>
                @endforelse
                <a href="{{ route('portal.cuotas') }}" class="small d-inline-block mt-2">Ver todas las cuotas</a>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted">Certificados</h2>
                @forelse ($certificados as $certificado)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-1 small">
                        <span><code>{{ $certificado->codigo }}</code><br>
                            <span class="text-muted">válido hasta {{ $certificado->valido_hasta->format('d/m/Y') }}</span>
                            @if ($certificado->estaAnulado()) <span class="badge text-bg-danger">Anulado</span>
                            @elseif ($certificado->estaVencido()) <span class="badge text-bg-secondary">Vencido</span>@endif
                        </span>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('certificados.descargar', $certificado) }}" title="Descargar PDF"><i class="bi bi-file-earmark-arrow-down"></i></a>
                    </div>
                @empty
                    <p class="small text-muted mb-0">Todavía no tenés certificados. Pedí uno iniciando el trámite "Certificado de matrícula vigente".</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<h2 class="h5 mt-4">Últimos trámites</h2>
@include('portal.tramites._tabla', ['tramites' => $tramites])
@endsection
