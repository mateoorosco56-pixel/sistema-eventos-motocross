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
                <a href="{{ route('dashboard') }}" class="pilot-menu-link active"><span>▦</span> Dashboard</a>
                <a href="{{ route('eventos.index') }}" class="pilot-menu-link"><span>🏁</span> Eventos disponibles</a>
                <a href="{{ route('piloto.mis-eventos') }}" class="pilot-menu-link"><span>◷</span> Mis eventos</a>
                <a href="{{ route('inscripciones.index') }}" class="pilot-menu-link"><span>✓</span> Inscripciones</a>
                <a href="{{ route('piloto.mis-eventos') }}" class="pilot-menu-link"><span>★</span> Resultados</a>
                <span class="pilot-menu-label pilot-menu-label-spaced">CUENTA</span>
                <a href="{{ route('profile.edit') }}" class="pilot-menu-link"><span>⚙</span> Mi perfil</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="pilot-menu-link pilot-menu-button"><span>↪</span> Cerrar sesión</button></form>
            </nav>
        </aside>

        <main class="pilot-main">
            <header class="pilot-topbar">
                    <div><span class="pilot-eyebrow">Panel del piloto</span><h1>Dashboard</h1></div>
                <div class="pilot-topbar-actions"><span class="pilot-date">{{ now()->format('d/m/Y') }}</span><a href="{{ route('profile.edit') }}" class="pilot-profile-link">{{ Auth::user()->name }}</a></div>
            </header>
            <section class="pilot-content">
                <div class="pilot-welcome-row">
                    <div><h2>Hola, {{ Auth::user()->name }}</h2><p>Este es el resumen de tu actividad en la competición.</p></div>
                    <a href="{{ route('eventos.index') }}" class="pilot-primary-action">Ver eventos</a>
                </div>
                <div class="pilot-stat-grid">
                    <div class="pilot-stat-card pilot-stat-cyan"><span class="pilot-stat-icon">🏁</span><strong>{{ $upcomingEvents ?? 0 }}</strong><span>Eventos próximos</span></div>
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
                    <section class="pilot-panel pilot-quick-panel"><span class="pilot-eyebrow">Acceso rápido</span><h3>Tu competición</h3><a href="{{ route('piloto.mis-eventos') }}" class="pilot-quick-link">Mis eventos <span>→</span></a><a href="{{ route('inscripciones.index') }}" class="pilot-quick-link">Mis inscripciones <span>→</span></a><a href="{{ route('profile.edit') }}" class="pilot-quick-link">Actualizar perfil <span>→</span></a></section>
                </div>
            </section>
        </main>
    </div>
</x-pilot-layout>
