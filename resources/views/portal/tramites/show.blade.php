@extends('layouts.app')

@section('titulo', 'Trámite '.$tramite->numero)

@section('contenido')
<a href="{{ route('portal.tramites.index') }}" class="small"><i class="bi bi-arrow-left"></i> Mis trámites</a>
<div class="d-flex flex-wrap align-items-center gap-2 mt-2 mb-3">
    <h1 class="h3 mb-0">Trámite {{ $tramite->numero }}</h1>
    <x-badge :estado="$tramite->estado" class="fs-6" />
</div>

<div class="row g-4">
    <div class="col-lg-7">
        @can('responder', $tramite)
            <div class="card border-warning shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5"><i class="bi bi-exclamation-circle text-warning"></i> El consejo observó tu trámite</h2>
                    <p class="small">{{ $tramite->movimientos->last()?->observacion }}</p>
                    <form method="POST" action="{{ route('portal.tramites.responder', $tramite) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="comentario" class="form-label">Tu respuesta</label>
                            <textarea name="comentario" id="comentario" rows="3" class="form-control @error('comentario') is-invalid @enderror" required minlength="10">{{ old('comentario') }}</textarea>
                            @error('comentario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="adjuntos" class="form-label">Nuevos PDF <span class="text-muted small">(opcional)</span></label>
                            <input type="file" name="adjuntos[]" id="adjuntos" accept="application/pdf,.pdf" multiple class="form-control @error('adjuntos.*') is-invalid @enderror">
                            @foreach ($errors->get('adjuntos.*') as $mensajes)<div class="invalid-feedback">{{ $mensajes[0] }}</div>@endforeach
                        </div>
                        <button class="btn btn-warning"><i class="bi bi-reply"></i> Responder y reenviar</button>
                    </form>
                </div>
            </div>
        @endcan

        @if ($tramite->certificado)
            <div class="alert alert-success d-flex justify-content-between align-items-center">
                <span><i class="bi bi-award"></i> Certificado emitido · código <code>{{ $tramite->certificado->codigo }}</code></span>
                <a class="btn btn-sm btn-success" href="{{ route('certificados.descargar', $tramite->certificado) }}"><i class="bi bi-download"></i> PDF</a>
            </div>
        @endif

        <div class="card shadow-sm mb-4"><div class="card-body">
            <h2 class="h6 text-uppercase text-muted">Datos</h2>
            @include('partials.datos-tramite')
        </div></div>

        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h6 text-uppercase text-muted">Adjuntos</h2>
            @include('partials.adjuntos')
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Historial</h2>
            @include('partials.historial')
        </div></div>
    </div>
</div>
@endsection
