@extends('layouts.app')

@section('titulo', 'Mis trámites')

@section('contenido')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Mis trámites</h1>
    @can('create', App\Models\Tramite::class)
        <a href="{{ route('portal.tramites.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Iniciar trámite</a>
    @endcan
</div>
@include('portal.tramites._tabla')
{{ $tramites->links() }}
@endsection
