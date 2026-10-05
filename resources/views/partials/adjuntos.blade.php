@if ($tramite->adjuntos->isEmpty())
    <p class="text-muted small mb-0">Sin adjuntos.</p>
@else
    <ul class="list-group list-group-flush">
        @foreach ($tramite->adjuntos as $adjunto)
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><i class="bi bi-file-earmark-pdf text-danger"></i> {{ $adjunto->nombre_original }}
                    <span class="text-muted small">({{ $adjunto->tamanioLegible() }})</span></span>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('adjuntos.descargar', $adjunto) }}"><i class="bi bi-download"></i> Descargar</a>
            </li>
        @endforeach
    </ul>
@endif
