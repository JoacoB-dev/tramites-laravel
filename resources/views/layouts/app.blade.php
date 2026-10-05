<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('titulo')@yield('titulo') · @endif{{ config('consejo.sigla') }} Matrículas y trámites</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-consejo shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ auth()->check() ? auth()->user()->rutaInicio() : route('inicio') }}">
            <i class="bi bi-buildings"></i> {{ config('consejo.sigla') }}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                @auth
                    @can('acceder-panel')
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('panel.inicio')) active @endif" href="{{ route('panel.inicio') }}">Inicio</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('panel.tramites.*')) active @endif" href="{{ route('panel.tramites.index') }}">Trámites</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('panel.profesionales.*')) active @endif" href="{{ route('panel.profesionales.index') }}">Profesionales</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('panel.cuotas.*')) active @endif" href="{{ route('panel.cuotas.index') }}">Cuotas</a></li>
                    @endcan
                    @can('acceder-portal')
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('portal.inicio')) active @endif" href="{{ route('portal.inicio') }}">Mi matrícula</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('portal.tramites.*')) active @endif" href="{{ route('portal.tramites.index') }}">Mis trámites</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('portal.cuotas')) active @endif" href="{{ route('portal.cuotas') }}">Mis cuotas</a></li>
                    @endcan
                @endauth
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('verificar.*')) active @endif" href="{{ route('verificar.buscar') }}">Verificar certificado</a></li>
            </ul>
            @auth
                <div class="d-flex align-items-center gap-2 text-white-50 small">
                    <span><i class="bi bi-person-circle"></i> {{ auth()->user()->name }} · {{ auth()->user()->rol->etiqueta() }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-light">Salir</button>
                    </form>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-sm btn-light">Ingresar</a>
            @endauth
        </div>
    </div>
</nav>

<main class="container py-4 flex-grow-1">
    <x-alertas />
    @yield('contenido')
</main>

<footer class="border-top bg-white py-3 small text-muted">
    <div class="container d-flex flex-wrap justify-content-between gap-2">
        <span>{{ config('consejo.nombre') }} — consejo ficticio, datos de ejemplo.</span>
        <span>Proyecto de portfolio · Laravel {{ app()->version() }}</span>
    </div>
</footer>
@stack('scripts')
</body>
</html>
