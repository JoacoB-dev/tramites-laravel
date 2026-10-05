<dl class="row mb-0 small">
    <dt class="col-sm-4">Tipo</dt><dd class="col-sm-8">{{ $tramite->tipo->nombre }}</dd>
    <dt class="col-sm-4">Iniciado</dt><dd class="col-sm-8">{{ $tramite->created_at->format('d/m/Y H:i') }}</dd>
    @if ($tramite->resuelto_en)
        <dt class="col-sm-4">Resuelto</dt><dd class="col-sm-8">{{ $tramite->resuelto_en->format('d/m/Y H:i') }}</dd>
    @endif
    @if ($tramite->tipo->esCambioDomicilio() && $tramite->datos)
        <dt class="col-sm-4">Nuevo domicilio</dt>
        <dd class="col-sm-8">{{ $tramite->datos['domicilio'] ?? '-' }}, {{ $tramite->datos['localidad'] ?? '-' }}</dd>
    @endif
    @if ($tramite->descripcion)
        <dt class="col-sm-4">Descripción</dt><dd class="col-sm-8">{{ $tramite->descripcion }}</dd>
    @endif
</dl>
