@extends('layouts.app')

@section('titulo', 'Iniciar trámite')

@section('contenido')
<h1 class="h3 mb-3">Iniciar trámite</h1>

<form method="POST" action="{{ route('portal.tramites.store') }}" enctype="multipart/form-data" id="form-tramite"
      data-max-kb="{{ config('consejo.adjuntos.max_kb') }}" data-max-archivos="{{ config('consejo.adjuntos.max_archivos') }}" novalidate>
    @csrf
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm"><div class="card-body">
                <div class="mb-3">
                    <label for="tipo_tramite_id" class="form-label">Tipo de trámite</label>
                    <select name="tipo_tramite_id" id="tipo_tramite_id" class="form-select @error('tipo_tramite_id') is-invalid @enderror" required>
                        <option value="">Elegí un tipo…</option>
                        @foreach ($tipos as $tipo)
                            {{-- Los data-* los usa resources/js/tramites.js para adaptar el formulario --}}
                            <option value="{{ $tipo->id }}" @selected(old('tipo_tramite_id') == $tipo->id)
                                    data-codigo="{{ $tipo->codigo }}"
                                    data-requiere-adjunto="{{ $tipo->requiere_adjunto ? 1 : 0 }}"
                                    data-descripcion="{{ $tipo->descripcion }}"
                                    data-arancel="@pesos($tipo->arancel)"
                                    data-dias="{{ $tipo->dias_estimados }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                    @error('tipo_tramite_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div id="campos-domicilio" class="border rounded p-3 mb-3 bg-light d-none">
                    <p class="small text-muted mb-2">Nuevo domicilio profesional (se actualiza en tu ficha cuando se aprueba el trámite).</p>
                    <x-campo nombre="domicilio" label="Domicilio" maxlength="150" />
                    <x-campo nombre="localidad" label="Localidad" maxlength="80" class="mb-0" />
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción / comentarios <span class="text-muted small">(opcional)</span></label>
                    <textarea name="descripcion" id="descripcion" rows="3" maxlength="2000" class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion') }}</textarea>
                    @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="adjuntos" class="form-label">Adjuntos PDF <span id="adjunto-obligatorio" class="badge text-bg-danger d-none">obligatorio</span></label>
                    <input type="file" name="adjuntos[]" id="adjuntos" accept="application/pdf,.pdf" multiple
                           class="form-control @if($errors->has('adjuntos') || $errors->has('adjuntos.*')) is-invalid @endif">
                    @error('adjuntos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @foreach ($errors->get('adjuntos.*') as $mensajes)
                        <div class="invalid-feedback">{{ $mensajes[0] }}</div>
                    @endforeach
                    <div class="form-text">Hasta {{ config('consejo.adjuntos.max_archivos') }} archivos PDF de {{ config('consejo.adjuntos.max_kb') / 1024 }} MB cada uno.</div>
                    <ul id="lista-adjuntos" class="list-unstyled small mt-2 mb-0"></ul>
                </div>

                <button class="btn btn-primary"><i class="bi bi-send"></i> Iniciar trámite</button>
                <a href="{{ route('portal.tramites.index') }}" class="btn btn-link">Cancelar</a>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm" id="info-tipo">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted">Sobre el trámite</h2>
                    <p class="small text-muted mb-0" id="info-vacia">Elegí un tipo para ver los requisitos.</p>
                    <div id="info-detalle" class="d-none">
                        <p class="small" id="info-descripcion"></p>
                        <p class="small mb-1"><strong>Arancel:</strong> <span id="info-arancel"></span></p>
                        <p class="small mb-0"><strong>Plazo estimado:</strong> <span id="info-dias"></span> días hábiles</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
