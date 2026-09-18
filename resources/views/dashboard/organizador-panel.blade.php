<x-organizer-layout>
    <div class="organizer-shell">
        <aside class="organizer-sidebar">
            <a href="{{ route('dashboard') }}" class="organizer-brand"><span class="organizer-brand-mark">AMC</span><span>Motocross</span></a>
            <div class="organizer-user">
                <div class="organizer-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div><strong>{{ Auth::user()->name }}</strong><small>Organizador</small></div>
            </div>
            <nav class="organizer-menu" aria-label="Navegación del organizador">
                <span class="organizer-menu-label">CENTRO DE GESTIÓN</span>
                <a href="{{ route('dashboard') }}" class="organizer-menu-link active"><span>▦</span> Dashboard</a>
                <a href="{{ route('organizador.mis-eventos') }}" class="organizer-menu-link"><span>◷</span> Mis eventos</a>
                <a href="{{ route('eventos.create') }}" class="organizer-menu-link"><span>＋</span> Crear evento</a>
                <a href="{{ route('inscripciones.index') }}" class="organizer-menu-link"><span>✓</span> Inscripciones</a>
                <a href="{{ route('participantes.index') }}" class="organizer-menu-link"><span>♙</span> Participantes</a>
                <a href="{{ route('resultados.index') }}" class="organizer-menu-link"><span>★</span> Resultados</a>
                <a href="{{ route('categorias.index') }}" class="organizer-menu-link"><span>≡</span> Categorías</a>
                <span class="organizer-menu-label organizer-menu-label-spaced">CUENTA</span>
                <a href="{{ route('profile.edit') }}" class="organizer-menu-link"><span>⚙</span> Mi perfil</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="organizer-menu-link organizer-menu-button"><span>↪</span> Cerrar sesión</button></form>
            </nav>
        </aside>

        <main class="organizer-main">
            <header class="organizer-topbar">
                <div><span class="organizer-eyebrow">Centro de operaciones</span><h1>Calendario</h1></div>
                <div class="organizer-topbar-actions">
                    <span class="organizer-date">{{ now()->format('d/m/Y') }}</span>
                    <div class="dropdown">
                        <button type="button" class="organizer-notification-button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Ver solicitudes de inscripción">
                            <span aria-hidden="true">🔔</span>
                            @if(auth()->user()->unreadNotifications->count())
                                <span class="organizer-notification-count">{{ auth()->user()->unreadNotifications->count() }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end organizer-notification-menu p-2">
                            <h6 class="dropdown-header">Solicitudes de inscripción</h6>
                            @forelse(auth()->user()->unreadNotifications->take(5) as $notification)
                                @php($data = $notification->data ?? [])
                                <a class="dropdown-item rounded py-2" href="{{ route('notifications.read', $notification->id) }}">
                                    <strong>{{ $data['participant_name'] ?? 'Nueva solicitud' }}</strong><br>
                                    <small class="text-muted">Solicitud para {{ $data['event_name'] ?? 'un evento' }}</small>
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
                    <a href="{{ route('profile.edit') }}" class="organizer-profile-link">{{ Auth::user()->name }}</a>
                </div>
            </header>

            <section class="organizer-content">
                <div class="organizer-heading-row">
                    <div><h2>Agenda del campeonato</h2><p>Organiza fechas, inscripciones y resultados desde un solo lugar.</p><span class="visually-hidden">Tu campeonato, en marcha.</span></div>
                    <a href="{{ route('eventos.create') }}" class="organizer-primary-action">+ Crear evento</a>
                </div>

                <div class="organizer-stat-grid">
                    <div class="organizer-stat-card organizer-stat-blue"><span class="organizer-stat-icon">◷</span><strong class="fs-2">{{ $events }}</strong><span>Eventos creados</span></div>
                    <div class="organizer-stat-card organizer-stat-orange"><span class="organizer-stat-icon">!</span><strong class="fs-2">{{ $pendingRegistrations }}</strong><span>Solicitudes pendientes</span></div>
                    <div class="organizer-stat-card organizer-stat-teal"><span class="organizer-stat-icon">♙</span><strong class="fs-2">{{ $participants }}</strong><span>Participantes</span></div>
                    <div class="organizer-stat-card organizer-stat-green"><span class="organizer-stat-icon">★</span><strong class="fs-2">{{ $updatedResults }}</strong><span>Resultados cargados</span></div>
                </div>

                <div class="organizer-dashboard-grid">
                    <section class="organizer-panel organizer-calendar-panel">
                        <div class="organizer-panel-heading"><div><span class="organizer-eyebrow">Calendario activo</span><h3>Próximas fechas</h3></div><a href="{{ route('organizador.mis-eventos') }}">Ver todos</a></div>
                        <div class="organizer-calendar-head"><span>FECHA</span><span>EVENTO</span><span>UBICACIÓN</span><span>ESTADO</span></div>
                        @forelse($nextEvents ?? [] as $event)
                            <a href="{{ route('eventos.show', $event) }}" class="organizer-calendar-row">
                                <span class="organizer-calendar-date"><strong>{{ \Carbon\Carbon::parse($event->event_date)->format('d') }}</strong><small>{{ \Carbon\Carbon::parse($event->event_date)->format('M Y') }}</small></span>
                                <span class="organizer-calendar-event"><strong>{{ $event->title }}</strong><small>{{ $event->description ? \Illuminate\Support\Str::limit($event->description, 55) : 'Competencia programada' }}</small></span>
                                <span class="organizer-calendar-location">{{ $event->location }}</span>
                                <span class="organizer-status">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                            </a>
                        @empty
                            <p class="organizer-empty-state">No hay eventos programados.</p>
                        @endforelse
                    </section>

                    <section class="organizer-panel organizer-actions-panel">
                        <span class="organizer-eyebrow">Gestión rápida</span><h3>Administrar campeonato</h3>
                        <a href="{{ route('inscripciones.index') }}" class="organizer-action-link"><span class="organizer-action-icon organizer-action-warning">!</span><span><strong>Revisar inscripciones</strong><small>{{ $pendingRegistrations }} pendientes de revisión</small></span><b>›</b></a>
                        <a href="{{ route('resultados.index') }}" class="organizer-action-link"><span class="organizer-action-icon organizer-action-success">★</span><span><strong>Cargar resultados</strong><small>Actualiza posiciones y puntos</small></span><b>›</b></a>
                        <a href="{{ route('participantes.index') }}" class="organizer-action-link"><span class="organizer-action-icon organizer-action-info">♙</span><span><strong>Participantes</strong><small>Consulta el registro de pilotos</small></span><b>›</b></a>
                    </section>
                </div>
            </section>
        </main>
    </div>
</x-organizer-layout>
