<div class="list-group shadow-sm">
    @forelse ($tramites as $tramite)
        <a href="{{ route('panel.tramites.show', $tramite) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            <span><strong>{{ $tramite->numero }}</strong> · {{ $tramite->tipo->nombre }}<br>
                <span class="small text-muted">{{ $tramite->profesional->nombre_completo }} · {{ $tramite->created_at->diffForHumans() }}</span></span>
            <x-badge :estado="$tramite->estado" />
        </a>
    @empty
        <div class="list-group-item text-muted small">{{ $vacio }}</div>
    @endforelse
</div>
