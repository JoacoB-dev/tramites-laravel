@extends('layouts.app')

@section('titulo', 'Panel')

@section('contenido')
<h1 class="h3 mb-3">Panel del consejo</h1>

<div class="row g-3 mb-4">
    @foreach (App\Enums\EstadoTramite::cases() as $estado)
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('panel.tramites.index', ['estado' => $estado->value]) }}" class="card shadow-sm text-decoration-none h-100 border-start border-4 border-{{ $estado->color() }}">
                <div class="card-body py-2">
                    <div class="small text-muted">{{ $estado->etiqueta() }}</div>
                    <div class="fs-3 fw-semibold text-body">{{ $porEstado[$estado->value] }}</div>
                </div>
            </a>
        </div>
    @endforeach
    <div class="col-6 col-md-4 col-xl-1">
        <div class="card shadow-sm h-100"><div class="card-body py-2">
            <div class="small text-muted">Activas</div>
            <div class="fs-3 fw-semibold">{{ $matriculasActivas }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-1">
        <a href="{{ route('panel.cuotas.index', ['estado' => 'vencida']) }}" class="card shadow-sm text-decoration-none h-100"><div class="card-body py-2">
            <div class="small text-muted">Cuotas venc.</div>
            <div class="fs-3 fw-semibold text-danger">{{ $cuotasVencidas }}</div>
        </div></a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <h2 class="h5">Sin asignar (más antiguos primero)</h2>
        @include('panel.tramites._lista', ['tramites' => $sinAsignar, 'vacio' => 'No hay trámites nuevos sin asignar.'])
    </div>
    <div class="col-lg-6">
        <h2 class="h5">Asignados a mí</h2>
        @include('panel.tramites._lista', ['tramites' => $misTramites, 'vacio' => 'No tenés trámites abiertos asignados.'])
    </div>
</div>
@endsection
