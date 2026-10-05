@extends('layouts.app')

@section('titulo', 'Verificar certificado')

@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <h1 class="h3">Verificar certificado</h1>
        <p class="text-body-secondary">Ingresá el código de verificación que figura al pie del certificado.</p>
        <form action="{{ route('verificar.buscar') }}" method="GET" class="input-group input-group-lg">
            <input type="text" name="codigo" class="form-control text-uppercase" placeholder="XXXX-XXXX-XXXX" aria-label="Código" maxlength="20" required>
            <button class="btn btn-success"><i class="bi bi-search"></i> Verificar</button>
        </form>
    </div>
</div>
@endsection
