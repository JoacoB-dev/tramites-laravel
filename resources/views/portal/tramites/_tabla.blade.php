@if ($tramites->isEmpty())
    <p class="text-muted">No iniciaste trámites todavía.</p>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle bg-white shadow-sm">
            <thead class="table-light"><tr><th>Número</th><th>Tipo</th><th>Iniciado</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @foreach ($tramites as $tramite)
                <tr>
                    <td class="fw-semibold">{{ $tramite->numero }}</td>
                    <td>{{ $tramite->tipo->nombre }}</td>
                    <td>{{ $tramite->created_at->format('d/m/Y') }}</td>
                    <td><x-badge :estado="$tramite->estado" /></td>
                    <td class="text-end"><a href="{{ route('portal.tramites.show', $tramite) }}" class="btn btn-sm btn-outline-primary">Ver</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
