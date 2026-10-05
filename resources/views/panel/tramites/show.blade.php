@extends('layouts.app')

@section('titulo', 'Trámite '.$tramite->numero)

@section('contenido')
<a href="{{ route('panel.tramites.index') }}" class="small"><i class="bi bi-arrow-left"></i> Trámites</a>
<div class="d-flex flex-wrap align-items-center gap-2 mt-2 mb-3">
    <h1 class="h3 mb-0">Trámite {{ $tramite->numero }}</h1>
    <x-badge :estado="$tramite->estado" class="fs-6" />
    @if ($tramite->empleado)<span class="small text-muted">· asignado a {{ $tramite->empleado->name }}</span>@endif
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-4"><div class="card-body">
            <h2 class="h6 text-uppercase text-muted">Profesional</h2>
            @php($p = $tramite->profesional)
            <p class="mb-1"><a href="{{ route('panel.profesionales.show', $p) }}">{{ $p->nombre_completo }}</a>
                · matrícula {{ $p->matricula }} · CUIT {{ $p->cuit_formateado }}</p>
            <p class="mb-0 small">Matrícula <x-badge :estado="$p->estado" />
                @if ($alDia)<span class="badge text-bg-success">Al día con cuotas</span>@else<span class="badge text-bg-danger">Con cuotas vencidas</span>@endif
            </p>
        </div></div>

        <div class="card shadow-sm mb-4"><div class="card-body">
            <h2 class="h6 text-uppercase text-muted">Datos del trámite</h2>
            @include('partials.datos-tramite')
        </div></div>

        <div class="card shadow-sm mb-4"><div class="card-body">
            <h2 class="h6 text-uppercase text-muted">Adjuntos</h2>
            @include('partials.adjuntos')
        </div></div>

        @if ($tramite->certificado)
            <div class="alert alert-success">Certificado emitido: <code>{{ $tramite->certificado->codigo }}</code>
                · <a href="{{ route('certificados.descargar', $tramite->certificado) }}">PDF</a>
                · <a href="{{ $tramite->certificado->urlVerificacion() }}" target="_blank">verificación pública</a></div>
        @endif

        @can('cambiarEstado', $tramite)
            <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <h2 class="h5">Cambiar estado</h2>
                    @if ($tramite->tipo->esCertificado() && ! $alDia)
                        <div class="alert alert-warning small">El profesional tiene cuotas vencidas: el sistema no va a dejar aprobar el certificado hasta que regularice.</div>
                    @endif
                    <form method="POST" action="{{ route('panel.tramites.estado', $tramite) }}" id="form-estado">
                        @csrf @method('PATCH')
                        <div class="mb-3">
                            @foreach ($tramite->estado->transicionesPermitidas() as $opcion)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="estado" id="estado-{{ $opcion->value }}" value="{{ $opcion->value }}"
                                           data-requiere-observacion="{{ $opcion->requiereObservacion() ? 1 : 0 }}" @checked(old('estado') === $opcion->value) required>
                                    <label class="form-check-label" for="estado-{{ $opcion->value }}"><x-badge :estado="$opcion" /></label>
                                </div>
                            @endforeach
                            @error('estado')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="observacion" class="form-label">Observación <span id="obs-obligatoria" class="badge text-bg-danger d-none">obligatoria</span></label>
                            <textarea name="observacion" id="observacion" rows="3" maxlength="2000" class="form-control @error('observacion') is-invalid @enderror"
                                      placeholder="Se le envía al profesional por mail">{{ old('observacion') }}</textarea>
                            @error('observacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-primary"><i class="bi bi-check2-square"></i> Guardar y notificar</button>
                    </form>
                </div>
            </div>
        @endcan
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Historial</h2>
            @include('partials.historial')
        </div></div>
    </div>
</div>
@endsection
