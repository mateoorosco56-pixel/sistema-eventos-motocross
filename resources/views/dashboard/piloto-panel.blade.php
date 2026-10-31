<x-pilot-layout>
    <div class="pilot-shell">
        <aside class="pilot-sidebar">
            <a href="{{ route('dashboard') }}" class="pilot-brand"><span class="pilot-brand-mark">AMC</span><span>Motocross</span></a>
            <div class="pilot-user">
                <div class="pilot-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div><strong>{{ Auth::user()->name }}</strong><small>Piloto</small></div>
            </div>
            <nav class="pilot-menu" aria-label="Navegación del piloto">
                <span class="pilot-menu-label">MENÚ PRINCIPAL</span>
                <a href="{{ route('dashboard') }}" class="pilot-menu-link active"><span>▦</span> Mis inscripciones</a>
                <a href="{{ route('eventos.index') }}" class="pilot-menu-link"><span>🏁</span> Eventos disponibles</a>
                <a href="{{ route('piloto.mis-eventos') }}" class="pilot-menu-link"><span>◷</span> Mis eventos</a>
                <a href="{{ route('inscripciones.index') }}" class="pilot-menu-link"><span>✓</span> Inscripciones</a>
                <a href="{{ route('resultados.index') }}" class="pilot-menu-link"><span>★</span> Resultados</a>
                <a href="#pilot-notifications" class="pilot-menu-link"><span>♧</span> Notificaciones @if(auth()->user()->unreadNotifications->count())<b class="pilot-sidebar-notification-count">{{ auth()->user()->unreadNotifications->count() }}</b>@endif</a>
                <span class="pilot-menu-label pilot-menu-label-spaced">CUENTA</span>
                <a href="{{ route('profile.edit') }}" class="pilot-menu-link"><span>⚙</span> Mi perfil</a>
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="pilot-logout-form">
                @csrf
                <button type="submit" class="pilot-menu-link pilot-logout-link"><span aria-hidden="true">↪</span>Cerrar sesión</button>
            </form>
        </aside>
        <nav class="pilot-mobile-nav" aria-label="Navegación móvil del piloto">
            <a href="{{ route('home') }}"><span aria-hidden="true">⌂</span>Inicio</a>
            <a href="{{ route('eventos.index') }}"><span aria-hidden="true">🏁</span>Eventos</a>
            <a class="is-active" href="{{ route('dashboard') }}"><span aria-hidden="true">▤</span>Mis inscripciones</a>
            <form method="POST" action="{{ route('logout') }}" class="pilot-mobile-logout-form">
                @csrf
                <button type="submit"><span aria-hidden="true">↪</span>Cerrar sesión</button>
            </form>
            <a href="{{ route('profile.edit') }}"><span aria-hidden="true">◉</span>Perfil</a>
        </nav>

        <main class="pilot-main">
            <header class="pilot-topbar">
                <div><span class="pilot-eyebrow">Panel del piloto</span><h1>Mis inscripciones</h1></div>
                <div class="pilot-topbar-actions"><span class="pilot-date">{{ now()->format('d/m/Y') }}</span><a href="{{ route('profile.edit') }}" class="pilot-profile-link" aria-label="Mi perfil"><span class="pilot-topbar-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span><span class="pilot-profile-name">{{ Auth::user()->name }}</span></a></div>
            </header>
            <section class="pilot-content">
                @if(Auth::user()->verification_status !== 'verified')
                    <div class="alert {{ Auth::user()->verification_status === 'rejected' ? 'alert-danger' : 'alert-warning' }}" role="status">
                        <strong>
                            @if(Auth::user()->verification_status === 'rejected')
                                Tu identidad fue rechazada.
                            @else
                                Tu identidad está pendiente de revisión.
                            @endif
                        </strong>
                        @if(Auth::user()->verification_rejection_reason)
                            {{ Auth::user()->verification_rejection_reason }}
                        @else
                            Para inscribirte en una carrera, primero debes verificar tu identidad.
                        @endif
                        <a href="{{ route('profile.edit') }}">Revisar mi perfil</a>
                    </div>
                @endif
                @php
                    $pilotRegistrations = $pilotRegistrations ?? collect();
                    $activePilotRegistrations = $pilotRegistrations->filter(fn ($registration) =>
                        in_array($registration->status, \App\Models\Inscripcion::ACTIVE_STATUSES, true)
                        && in_array($registration->event?->status, ['programado', 'en_curso'], true)
                    );
                    $historicalPilotRegistrations = $pilotRegistrations->reject(fn ($registration) =>
                        $activePilotRegistrations->contains('id', $registration->id)
                    );
                @endphp

                <section class="pilot-panel pilot-registration-panel" x-data="{ tab: 'active' }">
                    <div class="pilot-registration-tabs" role="tablist" aria-label="Filtrar inscripciones">
                        <button type="button" role="tab" :aria-selected="tab === 'active'" :class="{ 'is-selected': tab === 'active' }" @click="tab = 'active'">Activas <span>{{ $activePilotRegistrations->count() }}</span></button>
                        <button type="button" role="tab" :aria-selected="tab === 'history'" :class="{ 'is-selected': tab === 'history' }" @click="tab = 'history'">Historial <span>{{ $historicalPilotRegistrations->count() }}</span></button>
                        <a class="pilot-registration-all" href="{{ route('inscripciones.index') }}">Ver todas <span aria-hidden="true">→</span></a>
                    </div>

                    <div class="pilot-registration-list" x-show="tab === 'active'" role="tabpanel">
                        @forelse($activePilotRegistrations as $registration)
                            @php
                                $event = $registration->event;
                                $registrationLabel = $event?->status === 'en_curso' ? 'En curso' : ($registration->status === 'confirmada' ? 'Confirmado' : 'Pendiente');
                            @endphp
                            <article class="pilot-registration-card">
                                @if($event?->cover_path)
                                    <img class="pilot-registration-cover" src="{{ url('/storage/'.$event->cover_path) }}" alt="Portada de {{ $event->title }}" loading="lazy">
                                @else
                                    <div class="pilot-registration-cover pilot-registration-cover-empty" aria-hidden="true">🏁</div>
                                @endif
                                <div class="pilot-registration-details">
                                    <div class="pilot-registration-title-row"><h4>{{ $event?->title ?? 'Evento' }}</h4><span class="pilot-registration-status status-{{ $registration->status }}">{{ $registrationLabel }}</span></div>
                                    <p><span aria-hidden="true">▦</span> {{ $event?->event_date?->format('d/m/Y') }} <span class="pilot-detail-separator">·</span> {{ $event?->location ?? 'Ubicación pendiente' }}</p>
                                    <p><span aria-hidden="true">◉</span> {{ $registration->category?->name ?? $event?->categories?->pluck('name')->join(', ') ?? 'Categoría pendiente' }}</p>
                                    <div class="pilot-registration-actions">
                                        <a href="{{ route('piloto.inscripciones.show', $registration) }}" class="pilot-registration-primary">{{ $registration->status === 'pendiente' && $registration->payment_status !== 'aprobado' ? 'Ver pago' : 'Ver inscripción' }} <span aria-hidden="true">→</span></a>
                                        @if($registration->status === 'pendiente')
                                            <form action="{{ route('piloto.inscripciones.cancel', $registration) }}" method="POST" onsubmit="return confirm('¿Deseas cancelar esta inscripción?')">@csrf @method('DELETE')<button type="submit" class="pilot-registration-cancel">Cancelar</button></form>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="pilot-registration-empty"><span aria-hidden="true">▤</span><strong>No tienes inscripciones activas</strong><p>Explora los próximos eventos y reserva tu lugar.</p><a href="{{ route('eventos.index') }}">Ver eventos</a></div>
                        @endforelse
                    </div>

                    <div class="pilot-registration-list" x-show="tab === 'history'" x-cloak role="tabpanel">
                        @forelse($historicalPilotRegistrations as $registration)
                            @php
                                $event = $registration->event;
                                $registrationLabel = $event?->status === 'finalizado' ? 'Finalizado' : ucfirst($registration->status);
                            @endphp
                            <article class="pilot-registration-card">
                                @if($event?->cover_path)
                                    <img class="pilot-registration-cover" src="{{ url('/storage/'.$event->cover_path) }}" alt="Portada de {{ $event->title }}" loading="lazy">
                                @else
                                    <div class="pilot-registration-cover pilot-registration-cover-empty" aria-hidden="true">🏁</div>
                                @endif
                                <div class="pilot-registration-details">
                                    <div class="pilot-registration-title-row"><h4>{{ $event?->title ?? 'Evento' }}</h4><span class="pilot-registration-status status-history">{{ $registrationLabel }}</span></div>
                                    <p><span aria-hidden="true">▦</span> {{ $event?->event_date?->format('d/m/Y') }} <span class="pilot-detail-separator">·</span> {{ $event?->location ?? 'Ubicación pendiente' }}</p>
                                    <p><span aria-hidden="true">◉</span> {{ $registration->category?->name ?? $event?->categories?->pluck('name')->join(', ') ?? 'Categoría pendiente' }}</p>
                                    <div class="pilot-registration-actions">
                                        <a href="{{ route('piloto.inscripciones.show', $registration) }}" class="pilot-registration-primary">Ver inscripción <span aria-hidden="true">→</span></a>
                                        @if($event?->status === 'finalizado' && $event->results_published)<a href="{{ route('eventos.resultados', $event) }}" class="pilot-registration-secondary">Resultados</a>@endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="pilot-registration-empty"><span aria-hidden="true">◷</span><strong>Tu historial está vacío</strong><p>Las competencias finalizadas aparecerán aquí.</p></div>
                        @endforelse
                    </div>
                </section>

                <div class="pilot-stat-grid">
                    <div class="pilot-stat-card pilot-stat-cyan"><span class="pilot-stat-icon">🏁</span><strong>{{ $upcomingEvents ?? 0 }}</strong><span>Eventos disponibles</span></div>
                    <div class="pilot-stat-card pilot-stat-green"><span class="pilot-stat-icon">✓</span><strong>{{ $activeRegistrations ?? 0 }}</strong><span>Inscripciones activas</span></div>
                    <div class="pilot-stat-card pilot-stat-amber"><span class="pilot-stat-icon">★</span><strong>{{ $resultCount ?? 0 }}</strong><span>Resultados disponibles</span></div>
                </div>

                <div class="pilot-dashboard-grid">
                    <section class="pilot-panel">
                        <div class="pilot-panel-heading"><div><span class="pilot-eyebrow">Calendario</span><h3>Próximos eventos</h3></div><a href="{{ route('eventos.index') }}">Ver todos</a></div>
                        @forelse($nextEvents ?? [] as $event)
                            <a href="{{ route('eventos.show', $event) }}" class="pilot-event-row"><span class="pilot-event-date">{{ \Carbon\Carbon::parse($event->event_date)->format('d M') }}</span><span class="pilot-event-info"><strong>{{ $event->title }}</strong><small>{{ $event->location }}</small></span><span class="pilot-arrow">›</span></a>
                        @empty
                            <p class="pilot-empty-state">No hay eventos programados por el momento.</p>
                        @endforelse
                    </section>
                    <section class="pilot-panel pilot-quick-panel"><span class="pilot-eyebrow">Acceso rápido</span><h3>Tu competición</h3><a href="{{ route('piloto.mis-eventos') }}" class="pilot-quick-link">Mis eventos <span>→</span></a><a href="{{ route('inscripciones.index') }}" class="pilot-quick-link">Mis inscripciones <span>→</span></a><a href="{{ route('resultados.index') }}" class="pilot-quick-link">Resultados <span>→</span></a><a href="{{ route('profile.edit') }}" class="pilot-quick-link">Actualizar perfil <span>→</span></a></section>
                </div>

                <section class="pilot-panel pilot-notifications-panel" id="pilot-notifications">
                    <div class="pilot-panel-heading"><div><span class="pilot-eyebrow">Actividad</span><h3>Notificaciones recientes</h3></div><span>{{ auth()->user()->unreadNotifications->count() }} sin leer</span></div>
                    @forelse(auth()->user()->unreadNotifications->take(5) as $notification)
                        @php
                            $notificationData = $notification->data ?? [];
                            $notificationTitle = $notificationData['title'] ?? 'Nueva notificación';
                            $notificationMessage = $notificationData['message'] ?? 'Haz clic para revisar';
                        @endphp
                        <a class="pilot-notification-row" href="{{ route('notifications.read', $notification->id) }}"><span class="pilot-notification-icon">♧</span><span><strong>{{ $notificationTitle }}</strong><small>{{ $notificationMessage }}</small></span><b aria-hidden="true">›</b></a>
                    @empty
                        <p class="pilot-empty-state">No tienes notificaciones nuevas.</p>
                    @endforelse
                </section>
            </section>
        </main>
    </div>
</x-pilot-layout>
