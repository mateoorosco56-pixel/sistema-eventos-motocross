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

<body>

    <div class="min-vh-100 bg-light">

        {{-- Navegación --}}
        @unless(request()->routeIs('eventos.show'))
            @include('layouts.navigation')
        @endunless

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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @stack('scripts')
</body>

</html>