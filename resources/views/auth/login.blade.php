@extends('layouts.app')

@section('titulo', 'Ingresar')

@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Ingresar</h1>
                <form method="POST" action="{{ route('login.store') }}" novalidate>
                    @csrf
                    <x-campo nombre="email" label="Email" tipo="email" autocomplete="username" required autofocus />
                    <x-campo nombre="password" label="Contraseña" tipo="password" autocomplete="current-password" required />
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="recordarme" id="recordarme" value="1">
                        <label class="form-check-label" for="recordarme">Recordarme</label>
                    </div>
                    <button class="btn btn-primary w-100">Ingresar</button>
                </form>
            </div>
        </div>
        @if (app()->environment('local', 'testing') || config('app.demo'))
            <div class="alert alert-info small mt-3">
                <strong>Usuarios demo</strong> (contraseña <code>demo1234</code>):<br>
                admin@demo.local · empleado@demo.local · profesional@demo.local
            </div>
        @endif
    </div>
</div>
@endsection
