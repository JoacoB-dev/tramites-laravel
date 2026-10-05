@extends('layouts.app')

@section('contenido')
<div class="row align-items-center g-4 py-4">
    <div class="col-lg-7">
        <span class="badge text-bg-light border mb-2">Consejo ficticio · proyecto de portfolio</span>
        <h1 class="display-6 fw-semibold">Matrículas y trámites en línea</h1>
        <p class="lead text-body-secondary">{{ config('consejo.nombre') }}.</p>
        <p>Los profesionales matriculados inician trámites (visado de planos, certificado de matrícula vigente,
            cambio de domicilio), adjuntan PDF y siguen el estado. El personal del consejo los revisa,
            observa, aprueba o rechaza desde el panel interno.</p>
        <div class="d-flex flex-wrap gap-2">
            @auth
                <a href="{{ auth()->user()->rutaInicio() }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Ir a mi inicio</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Ingresar</a>
            @endauth
            <a href="{{ route('verificar.buscar') }}" class="btn btn-outline-secondary"><i class="bi bi-patch-check"></i> Verificar un certificado</a>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted">¿Recibiste un certificado?</h2>
                <p class="small">Ingresá el código impreso al pie para comprobar que lo emitió el consejo y que sigue vigente.</p>
                <form action="{{ route('verificar.buscar') }}" method="GET" class="input-group">
                    <input type="text" name="codigo" class="form-control text-uppercase" placeholder="XXXX-XXXX-XXXX" aria-label="Código de verificación" required>
                    <button class="btn btn-success">Verificar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
