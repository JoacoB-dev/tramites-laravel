{{-- Historial de estados de un trámite ($tramite con movimientos.user cargado) --}}
<ol class="historial list-unstyled mb-0">
    @foreach ($tramite->movimientos as $mov)
        <li class="historial-item">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <x-badge :estado="$mov->estado_nuevo" />
                <span class="small text-muted">{{ $mov->created_at->format('d/m/Y H:i') }}</span>
                <span class="small">· {{ $mov->user?->name ?? 'Sistema' }}</span>
            </div>
            @if ($mov->observacion)
                <p class="mb-0 mt-1 small text-body-secondary">{{ $mov->observacion }}</p>
            @endif
        </li>
    @endforeach
</ol>
