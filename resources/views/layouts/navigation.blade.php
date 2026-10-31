<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm amc-main-navbar {{ auth()->check() && auth()->user()->role === 'administrador' ? 'amc-admin-navbar' : '' }}">
    <div class="container-fluid">

        {{-- Logo / Nombre --}}
        <a class="navbar-brand fw-bold" href="{{ route('home') }}">
            AMC
        </a>

        @unless(auth()->check() && auth()->user()->role === 'administrador')
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAMC" aria-controls="navbarAMC" aria-expanded="false" aria-label="Abrir menú de navegación">
                <span class="navbar-toggler-icon"></span>
            </button>
        @endunless

        {{-- Navegación --}}
        <div class="{{ auth()->check() && auth()->user()->role === 'administrador' ? 'navbar-collapse amc-admin-navigation' : 'collapse navbar-collapse' }}" id="navbarAMC">

            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                {{-- Inicio --}}
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Inicio</a></li>

                {{-- Eventos --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('eventos.*', 'organizador.mis-eventos') ? 'active' : '' }}" href="{{ auth()->check() && auth()->user()->role === 'organizador' ? route('organizador.mis-eventos') : route('eventos.index') }}">
                        @auth
                            {{ auth()->user()->role === 'organizador' ? 'Mis eventos' : 'Eventos' }}
                        @else
                            Eventos
                        @endauth
                        @auth
                        @if(auth()->user()->role === 'piloto')
                            @php
                                $nuevosEventos = auth()->user()->unreadNotifications->where('type', \App\Notifications\EventoPublicado::class)->count();
                            @endphp
                            @if($nuevosEventos > 0)
                                <span class="badge bg-danger rounded-pill ms-1" title="Hay {{ $nuevosEventos }} eventos nuevos disponibles">{{ $nuevosEventos }}</span>
                            @endif
                        @endif
                        @endauth
                    </a>
                </li>

                <li class="nav-item"><a class="nav-link {{ request()->routeIs('resultados.index') ? 'active' : '' }}" href="{{ route('resultados.index') }}">Resultados</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('categorias.index') ? 'active' : '' }}" href="{{ route('categorias.index') }}">Categorías</a></li>

                @auth
                @if(in_array(auth()->user()->role, ['administrador', 'organizador']))
                    {{-- Participantes --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('participantes.*') ? 'active' : '' }}" href="{{ route('participantes.index') }}">
                            Participantes
                        </a>
                    </li>
                @endif

                @if(auth()->user()->role === 'piloto')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('piloto.mis-eventos') ? 'active' : '' }}" href="{{ route('piloto.mis-eventos') }}">
                            Mis eventos
                        </a>
                    </li>
                @endif

                @if(in_array(auth()->user()->role, ['administrador', 'organizador', 'piloto']))
                    {{-- Inscripciones --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('inscripciones.*') ? 'active' : '' }}" href="{{ route('inscripciones.index') }}">
                            Inscripciones
                        </a>
                    </li>
                @endif

                @if(auth()->user()->role === 'piloto')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('eventos.resultados', 'eventos.ranking') ? 'active' : '' }}" href="{{ route('piloto.mis-eventos') }}">
                            Resultados
                        </a>
                    </li>
                @endif

                @if(in_array(auth()->user()->role, ['usuario', 'piloto']))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('organizer-requests.create') }}">Solicitar ser organizador</a>
                    </li>
                @endif

                @if(auth()->user()->role === 'administrador')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Usuarios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.organizer-requests.*') ? 'active' : '' }}" href="{{ route('admin.organizer-requests.index') }}">Solicitudes</a>
                    </li>
                @endif
                @endauth

            </ul>

            {{-- Usuario --}}
            @auth
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2 ms-lg-auto">
                <a class="nav-link text-white" href="{{ route('profile.edit') }}">
                    {{ Auth::user()->name }}
                </a>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    @method('POST')
                    <button type="submit" class="btn btn-outline-light w-100">Cerrar sesión</button>
                </form>
            </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-light">Iniciar sesión</a>
            @endauth

        </div>

        @auth
            @if(in_array(auth()->user()->role, ['organizador', 'administrador']))
                <div class="dropup dropdown position-fixed bottom-0 end-0 m-3" style="z-index: 1050;">
                    <button class="btn btn-warning position-relative rounded-circle shadow" style="width: 56px; height: 56px;" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Solicitudes nuevas">
                        🔔
                        @if(auth()->user()->unreadNotifications->count())
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ auth()->user()->unreadNotifications->count() }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-2 amc-notification-menu">
                        <h6 class="dropdown-header">Solicitudes de inscripción</h6>
                        @forelse(auth()->user()->unreadNotifications->take(5) as $notification)
                            @php
                                $data = $notification->data ?? [];
                                $title = $data['title'] ?? ($data['participant_name'] ?? 'Nueva notificación');
                                $subtitle = $data['message'] ?? ($data['event_name'] ? 'Solicitud para ' . $data['event_name'] : 'Haz clic para ver más detalle');
                            @endphp
                            <a class="dropdown-item rounded py-2" href="{{ route('notifications.read', $notification->id) }}">
                                <strong>{{ $title }}</strong><br>
                                <small class="text-muted">{{ $subtitle }}</small>
                            </a>
                        @empty
                            <span class="dropdown-item-text text-muted">No tienes solicitudes nuevas.</span>
                        @endforelse
                        @if(auth()->user()->unreadNotifications->count())
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center fw-semibold" href="{{ route('inscripciones.index') }}">Ver todas las inscripciones</a>
                        @endif
                    </div>
                </div>
            @endif
        @endauth

    </div>
</nav>