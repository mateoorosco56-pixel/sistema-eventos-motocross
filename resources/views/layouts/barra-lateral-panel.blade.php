<aside class="workspace-sidebar">
    <a href="{{ route('home') }}" class="workspace-brand" aria-label="AMC, ir al inicio">
        <span class="workspace-brand-mark">AMC</span>
        <span>MX <small>EVENTOS</small></span>
    </a>

    @auth
        <div class="workspace-sidebar-user">
            <span class="workspace-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
            <span><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(auth()->user()->role) }}</small></span>
        </div>
    @endauth

    <nav class="workspace-menu" aria-label="Navegación principal">
        <a class="workspace-menu-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}"><span aria-hidden="true">⌂</span>Inicio</a>
        @if(auth()->user()?->role === 'organizador')
            <a class="workspace-menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span aria-hidden="true">▦</span>Panel</a>
        @endif
        <a class="workspace-menu-link {{ request()->routeIs('eventos.index', 'organizador.mis-eventos', 'eventos.programados') ? 'active' : '' }}" href="{{ auth()->user()?->role === 'organizador' ? route('organizador.mis-eventos') : route('eventos.index') }}"><span aria-hidden="true">◷</span>{{ auth()->user()?->role === 'organizador' ? 'Mis eventos' : 'Eventos' }}</a>

        @auth
            @if(in_array(auth()->user()->role, ['organizador', 'administrador']))
                <a class="workspace-menu-link {{ request()->routeIs('eventos.create') ? 'active' : '' }}" href="{{ route('eventos.create') }}"><span aria-hidden="true">＋</span>Crear evento</a>
                <a class="workspace-menu-link {{ request()->routeIs('organizador.circuitos.*') ? 'active' : '' }}" href="{{ route('organizador.circuitos.index') }}"><span aria-hidden="true">▧</span>Circuitos</a>
            @endif
            @if(in_array(auth()->user()->role, ['organizador', 'administrador', 'piloto']))
                <a class="workspace-menu-link {{ request()->routeIs('inscripciones.*') ? 'active' : '' }}" href="{{ route('inscripciones.index') }}"><span aria-hidden="true">▤</span>Inscripciones</a>
            @endif
            @if(in_array(auth()->user()->role, ['organizador', 'administrador']))
                <a class="workspace-menu-link {{ request()->routeIs('participantes.*') ? 'active' : '' }}" href="{{ route('participantes.index') }}"><span aria-hidden="true">♙</span>Participantes</a>
            @endif
        @endauth

        <a class="workspace-menu-link {{ request()->routeIs('resultados.*', 'eventos.resultados', 'eventos.ranking') ? 'active' : '' }}" href="{{ route('resultados.index') }}"><span aria-hidden="true">★</span>Resultados</a>
        <a class="workspace-menu-link {{ request()->routeIs('categorias.*') ? 'active' : '' }}" href="{{ route('categorias.index') }}"><span aria-hidden="true">≡</span>Categorías</a>

        @auth
            <div class="dropdown">
                <button class="workspace-menu-link workspace-notifications-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span aria-hidden="true">♧</span>Notificaciones
                    @if(auth()->user()->unreadNotifications->count())
                        <span class="workspace-notification-count">{{ auth()->user()->unreadNotifications->count() }}</span>
                    @endif
                </button>
                <div class="dropdown-menu workspace-notification-menu">
                    @forelse(auth()->user()->unreadNotifications->take(5) as $notification)
                        @php
                            $notificationData = $notification->data ?? [];
                            $notificationTitle = $notificationData['title'] ?? ($notificationData['participant_name'] ?? 'Nueva notificación');
                            $notificationSubtitle = $notificationData['message'] ?? (isset($notificationData['event_name']) ? 'Solicitud para '.$notificationData['event_name'] : 'Haz clic para revisar');
                        @endphp
                        <a class="dropdown-item" href="{{ route('notifications.read', $notification->id) }}"><strong>{{ $notificationTitle }}</strong><small class="d-block text-muted">{{ $notificationSubtitle }}</small></a>
                    @empty
                        <span class="dropdown-item-text text-muted">No tienes notificaciones nuevas.</span>
                    @endforelse
                    @if(auth()->user()->unreadNotifications->count() && in_array(auth()->user()->role, ['organizador', 'administrador']))
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-center" href="{{ route('inscripciones.index') }}">Ver inscripciones</a>
                    @endif
                </div>
            </div>
            <a class="workspace-menu-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}" href="{{ route('profile.edit') }}"><span aria-hidden="true">◉</span>Perfil</a>
        @endauth
    </nav>
</aside>