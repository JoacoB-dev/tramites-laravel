@extends('layouts.app')

@section('titulo', 'Mis cuotas')

@section('contenido')
<h1 class="h3">Mis cuotas</h1>
@if ($deudaVencida > 0)
    <div class="alert alert-danger">Tenés cuotas vencidas por <strong>@pesos($deudaVencida)</strong>. Con deuda vencida no se puede emitir el certificado de matrícula vigente.</div>
@else
    <div class="alert alert-success">No tenés cuotas vencidas.</div>
@endif
<p class="small text-muted">El pago se registra en el consejo (en sede o por transferencia). Este portal no cobra en línea.</p>

<div class="table-responsive">
    <table class="table bg-white shadow-sm align-middle">
        <thead class="table-light"><tr><th>Período</th><th>Vencimiento</th><th class="text-end">Importe</th><th>Estado</th><th>Pagada</th></tr></thead>
        <tbody>
        @foreach ($cuotas as $cuota)
            <tr>
                <td>{{ ucfirst($cuota->periodoLegible()) }}</td>
                <td>{{ $cuota->vencimiento->format('d/m/Y') }}</td>
                <td class="text-end">@pesos($cuota->importe)</td>
                <td><x-badge :estado="$cuota->estado()" /></td>
                <td>{{ $cuota->pagada_en?->format('d/m/Y') ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $cuotas->links() }}
@endsection
