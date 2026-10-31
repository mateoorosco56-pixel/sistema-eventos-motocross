<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'AMC') }}</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
    $pilotFlow = auth()->check() && auth()->user()->role === 'piloto';
    $pilotPaymentPage = $pilotFlow && request()->routeIs('piloto.inscripciones.show');
    $pilotResultsPage = $pilotFlow && request()->routeIs('resultados.index');
    $workspaceLayout = (auth()->check() && auth()->user()->role === 'organizador')
        || request()->routeIs('eventos.create', 'eventos.show', 'inscripciones.create', 'inscripciones.index');
@endphp

<body class="{{ $workspaceLayout ? 'workspace-app-body' : '' }} {{ $pilotFlow ? 'pilot-flow-body' : '' }}">

    @if($workspaceLayout)
        <div class="workspace-shell">
            @include('layouts.barra-lateral-panel')
            <div class="workspace-main">
                <header class="workspace-topbar">
                    <a class="workspace-topbar-home" href="{{ route('home') }}">AMC <span>Motocross</span></a>
                    <div class="workspace-topbar-actions">
                        @auth
                            <a class="workspace-user-link" href="{{ route('profile.edit') }}">{{ auth()->user()->name }} <small>{{ ucfirst(auth()->user()->role) }}</small></a>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button class="workspace-logout" type="submit">Cerrar sesión</button>
                            </form>
                        @else
                            <a class="workspace-logout" href="{{ route('login') }}">Iniciar sesión</a>
                        @endauth
                    </div>
                </header>
                <main class="workspace-content">{{ $slot }}</main>
            </div>
        </div>
    @else
    <div class="min-vh-100 bg-light">

        @if($pilotFlow && request()->routeIs('profile.edit'))
            <header class="pilot-profile-navbar">
                <a href="{{ route('home') }}" class="pilot-profile-navbar-brand">AMC</a>
                <div class="pilot-profile-navbar-actions">
                    <span>{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit">Cerrar sesión</button>
                    </form>
                </div>
            </header>
        @elseif(! request()->routeIs('eventos.show', 'piloto.notificaciones.index') && ! $pilotResultsPage && ! $pilotPaymentPage)
            @include('layouts.navigation')
        @endif

        {{-- Encabezado de página --}}
        @isset($header)
            <header class="bg-white border-bottom">
                <div class="container-fluid py-3">
                    {{ $header }}
                </div>
            </header>
        @endisset

        {{-- Contenido --}}
        <main>
            {{ $slot }}
        </main>

    </div>
    @endif

    @if($pilotFlow && ! $pilotPaymentPage)
        @include('layouts.navegacion-flujo-piloto')
    @endif

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @stack('scripts')
</body>

</html>