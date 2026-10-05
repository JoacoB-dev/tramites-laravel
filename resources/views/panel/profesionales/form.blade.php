@extends('layouts.app')

@php($editando = $profesional->exists)
@section('titulo', $editando ? 'Editar '.$profesional->matricula : 'Nueva matrícula')

@section('contenido')
<h1 class="h3 mb-3">{{ $editando ? 'Editar '.$profesional->nombre_completo : 'Nueva matrícula' }}</h1>

<form method="POST" action="{{ $editando ? route('panel.profesionales.update', $profesional) : route('panel.profesionales.store') }}" class="card card-body shadow-sm" novalidate>
    @csrf
    @if ($editando) @method('PUT') @endif
    <div class="row">
        <div class="col-md-3"><x-campo nombre="matricula" label="Matrícula" :valor="$profesional->matricula" placeholder="IC-1234" required /></div>
        <div class="col-md-4"><x-campo nombre="apellido" label="Apellido" :valor="$profesional->apellido" required /></div>
        <div class="col-md-5"><x-campo nombre="nombre" label="Nombre/s" :valor="$profesional->nombre" required /></div>
        <div class="col-md-4">
            <x-campo nombre="cuit" label="CUIT" :valor="$profesional->exists ? $profesional->cuit_formateado : null" placeholder="20-12345678-6" class="js-cuit" required
                     ayuda="Se valida el dígito verificador." />
        </div>
        <div class="col-md-4"><x-campo nombre="email" label="Email" tipo="email" :valor="$profesional->email" required /></div>
        <div class="col-md-4"><x-campo nombre="telefono" label="Teléfono" :valor="$profesional->telefono" /></div>
        <div class="col-md-6"><x-campo nombre="domicilio" label="Domicilio profesional" :valor="$profesional->domicilio" required /></div>
        <div class="col-md-6"><x-campo nombre="localidad" label="Localidad" :valor="$profesional->localidad" required /></div>
        <div class="col-md-6">
            <x-campo nombre="profesion" label="Profesión / título" :valor="$profesional->profesion" list="profesiones" required />
            <datalist id="profesiones">
                <option value="Ingeniero/a civil"><option value="Arquitecto/a"><option value="Maestro/a mayor de obras">
                <option value="Ingeniero/a electricista"><option value="Agrimensor/a">
            </datalist>
        </div>
        <div class="col-md-3"><x-campo nombre="fecha_matriculacion" label="Fecha de matriculación" tipo="date" :valor="$profesional->fecha_matriculacion?->toDateString()" required /></div>
    </div>
    @unless ($editando)
        <div class="form-check mb-3">
            <input type="hidden" name="crear_acceso" value="0">
            <input class="form-check-input" type="checkbox" name="crear_acceso" id="crear_acceso" value="1" @checked(old('crear_acceso'))>
            <label class="form-check-label" for="crear_acceso">Crear usuario para el portal (se muestra una contraseña temporal una sola vez)</label>
        </div>
    @endunless
    <div>
        <button class="btn btn-primary">Guardar</button>
        <a href="{{ $editando ? route('panel.profesionales.show', $profesional) : route('panel.profesionales.index') }}" class="btn btn-link">Cancelar</a>
    </div>
</form>
@endsection
