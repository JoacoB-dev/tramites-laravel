@extends('layouts.app')

@section('titulo', 'Verificación '.$codigo)

@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-9 col-lg-7">
        <h1 class="h4 mb-3">Verificación del código <code>{{ $codigo }}</code></h1>

        @if (! $certificado)
            <div class="alert alert-danger" data-resultado="inexistente">
                <h2 class="h5"><i class="bi bi-x-octagon"></i> Código inexistente</h2>
                <p class="mb-0">No hay ningún certificado emitido con ese código. Revisá que esté bien escrito.</p>
            </div>
        @else
            @php($p = $certificado->profesional)
            @if ($certificado->estaAnulado())
                <div class="alert alert-danger" data-resultado="anulado">
                    <h2 class="h5"><i class="bi bi-x-octagon"></i> Certificado ANULADO</h2>
                    <p class="mb-0">Anulado el {{ $certificado->anulado_en->format('d/m/Y') }}. Motivo: {{ $certificado->motivo_anulacion }}</p>
                </div>
            @elseif ($certificado->estaVencido())
                <div class="alert alert-warning" data-resultado="vencido">
                    <h2 class="h5"><i class="bi bi-clock-history"></i> Certificado VENCIDO</h2>
                    <p class="mb-0">Fue válido hasta el {{ $certificado->valido_hasta->format('d/m/Y') }}.</p>
                </div>
            @else
                <div class="alert alert-success" data-resultado="valido">
                    <h2 class="h5"><i class="bi bi-patch-check-fill"></i> Certificado VÁLIDO</h2>
                    <p class="mb-0">Emitido por el consejo, válido hasta el {{ $certificado->valido_hasta->format('d/m/Y') }}.</p>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Profesional</dt><dd class="col-sm-7">{{ $p->nombre_completo }}</dd>
                        <dt class="col-sm-5">Matrícula</dt><dd class="col-sm-7">{{ $p->matricula }}</dd>
                        <dt class="col-sm-5">Profesión</dt><dd class="col-sm-7">{{ $p->profesion }}</dd>
                        <dt class="col-sm-5">Emitido</dt><dd class="col-sm-7">{{ $certificado->emitido_en->format('d/m/Y') }}</dd>
                        <dt class="col-sm-5">Estado actual de la matrícula</dt><dd class="col-sm-7"><x-badge :estado="$p->estado" /></dd>
                    </dl>
                </div>
            </div>
            @if (! $p->estaActiva())
                <p class="small text-danger mt-2">Atención: después de emitido el certificado, la matrícula cambió de estado.</p>
            @endif
        @endif

        <a href="{{ route('verificar.buscar') }}" class="btn btn-link px-0 mt-3">Verificar otro código</a>
    </div>
</div>
@endsection
