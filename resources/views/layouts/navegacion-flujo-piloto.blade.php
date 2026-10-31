@php
    $isResultsPage = request()->routeIs('resultados.index', 'eventos.resultados', 'eventos.ranking');
    $thirdLabel = $isResultsPage ? 'Resultados' : 'Mis inscripciones';
    $thirdRoute = $isResultsPage ? route('resultados.index') : route('dashboard');
@endphp
<nav class="pilot-flow-mobile-nav" aria-label="Navegación del piloto">
    <a href="{{ route('home') }}" @class(['is-active' => request()->routeIs('home')])><span aria-hidden="true">⌂</span>Inicio</a>
    <a href="{{ route('eventos.index') }}" @class(['is-active' => request()->routeIs('eventos.index', 'eventos.show')])><span aria-hidden="true">🏁</span>Eventos</a>
    <a href="{{ $thirdRoute }}" @class(['is-active' => $isResultsPage || request()->routeIs('dashboard', 'inscripciones.*', 'piloto.inscripciones.*', 'piloto.mis-eventos')])><span aria-hidden="true">{{ $isResultsPage ? '♜' : '▤' }}</span>{{ $thirdLabel }}</a>
    <a href="{{ route('piloto.notificaciones.index') }}" @class(['is-active' => request()->routeIs('piloto.notificaciones.index')])><span class="pilot-flow-bell" aria-hidden="true">♧</span>Notificaciones
        @if(auth()->user()->unreadNotifications->count())<b class="pilot-flow-unread-count">{{ auth()->user()->unreadNotifications->count() }}</b>@endif
    </a>
    <a href="{{ route('profile.edit') }}" @class(['is-active' => request()->routeIs('profile.*')])><span aria-hidden="true">◉</span>Perfil</a>
</nav>