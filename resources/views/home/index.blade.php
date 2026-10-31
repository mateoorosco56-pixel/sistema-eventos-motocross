<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#07131c">
    <title>Motocross Bolivia | Competencias y resultados</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800;900&family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
</head>
<body>
@php
    $featuredEvent = $upcomingEvents->first();
    $fallbackCovers = [
        asset('imagenes/WhatsApp Image 2026-10-01 at 19.06.13 (2).jpeg'),
        asset('imagenes/WhatsApp Image 2026-10-01 at 19.06.13 (3).jpeg'),
    ];
    $heroSlides = $upcomingEvents->values()->map(function ($event, $index) use ($fallbackCovers) {
        return [
            'title' => $event->title,
            'date' => $event->event_date?->format('d/m/Y'),
            'dateIso' => $event->event_date?->format('Y-m-d'),
            'location' => $event->location,
            'image' => $event->cover_path ? url('/storage/'.$event->cover_path) : $fallbackCovers[$index % count($fallbackCovers)],
            'categories' => $event->categories->pluck('name')->take(4)->values(),
            'fallbackCategory' => $event->category?->name ?: 'Inscripciones abiertas',
            'eventUrl' => route('eventos.show', $event),
            'registrationUrl' => route('inscripciones.create', ['event_id' => $event->id]),
            'mapUrl' => 'https://www.google.com/maps/search/?api=1&query='.urlencode($event->location),
        ];
    });
    $heroImage = $featuredEvent?->cover_path
        ? url('/storage/'.$featuredEvent->cover_path)
        : asset('imagenes/WhatsApp Image 2026-10-01 at 19.06.13 (4).jpeg');
@endphp
<div class="site-shell">
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}" aria-label="Motocross Bolivia, inicio">
            <span class="brand-mountain" aria-hidden="true">▲</span>
            <span class="brand-copy"><strong>MOTOCROSS</strong><b>BOLIVIA <i></i></b></span>
        </a>
        <nav class="main-nav" aria-label="Navegación principal">
            <a class="active" href="{{ route('home') }}">Inicio</a>
            <a href="#eventos">Eventos</a>
            <a href="#resultados">Resultados</a>
            <a href="#ranking">Ranking</a>
            <a href="#campeonatos">Campeonatos</a>
            <a href="#circuitos">Circuitos</a>
        </nav>
        <div class="top-actions">
            <a class="icon-button" href="{{ route('eventos.index') }}" aria-label="Buscar eventos" title="Buscar eventos"><span class="search-icon"></span></a>
            @auth
                <a class="login-button" href="{{ route('dashboard') }}"><span class="user-icon">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span> Mi cuenta</a>
            @else
                <a class="login-button" href="{{ route('login') }}"><span class="user-icon">+</span> Iniciar sesión</a>
                <a class="register-link" href="{{ route('register') }}">Crear cuenta</a>
            @endauth
        </div>
        <details class="mobile-menu">
            <summary aria-label="Abrir navegación"><span></span><span></span><span></span></summary>
            <nav aria-label="Navegación móvil">
                <a href="{{ route('home') }}">Inicio</a><a href="#eventos">Eventos</a>
                <a href="#resultados">Resultados</a><a href="#ranking">Ranking</a>
                <a href="#campeonatos">Campeonatos</a><a href="#circuitos">Circuitos</a>
                @guest<a href="{{ route('register') }}">Crear cuenta</a>@endguest
            </nav>
        </details>
    </header>

    <main>
        <section class="hero" style="--hero-image: url('{{ $heroImage }}')">
            @if($heroSlides->count() > 1)<button class="hero-arrow hero-arrow-left" type="button" data-slide-direction="-1" aria-label="Evento anterior">‹</button>@endif
            <div class="hero-content">
                <span class="eyebrow"><span class="live-dot"></span> Próximo evento</span>
                @if($featuredEvent)
                    <h1 id="hero-title">{{ $featuredEvent->title }}</h1>
                    <div class="hero-meta">
                        <span><b class="calendar-icon">▦</b><span id="hero-date">{{ $featuredEvent->event_date?->format('d/m/Y') }}</span></span>
                        <span><b class="pin-icon">●</b><span id="hero-location">{{ $featuredEvent->location }}</span></span>
                    </div>
                    <div class="hero-categories" id="hero-categories">
                        @forelse($featuredEvent->categories->take(4) as $category)<span>{{ $category->name }}</span>
                        @empty
                            @if($featuredEvent->category)<span>{{ $featuredEvent->category->name }}</span>@else<span>Inscripciones abiertas</span>@endif
                        @endforelse
                    </div>
                    <div class="hero-buttons">
                        <a class="button button-green" id="hero-event-link" href="{{ route('eventos.show', $featuredEvent) }}"><span class="calendar-icon">▦</span> Ver evento</a>
                        <a class="button button-ghost" id="hero-registration-link" href="{{ route('inscripciones.create', ['event_id' => $featuredEvent->id]) }}"><span class="user-icon">+</span> Inscribirme</a>
                    </div>
                @else
                    <h1>La próxima carrera<br>empieza aquí</h1><p class="hero-empty">Pronto anunciaremos nuevas competencias.</p>
                    <div class="hero-buttons"><a class="button button-green" href="{{ route('eventos.index') }}">Explorar eventos</a></div>
                @endif
            </div>
            <aside class="event-widget">
                <div class="widget-heading"><strong>FALTAN</strong><a href="{{ route('eventos.index') }}">Calendario <span>↗</span></a></div>
                <div class="countdown" @if($featuredEvent) data-countdown="{{ $featuredEvent->event_date?->format('Y-m-d') }}" @endif>
                    <div><strong data-days>--</strong><span>DÍAS</span></div><div><strong data-hours>--</strong><span>HORAS</span></div>
                    <div><strong data-minutes>--</strong><span>MIN</span></div><div><strong data-seconds>--</strong><span>SEG</span></div>
                </div>
                <div class="map-heading"><span class="map-pin">●</span> UBICACIÓN DEL CIRCUITO</div>
                <a class="mini-map" id="hero-map-link" href="{{ $featuredEvent ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($featuredEvent->location) : route('eventos.index') }}" target="_blank" rel="noopener noreferrer" aria-label="Ver ubicación del circuito en el mapa">
                    <span class="map-road road-one"></span><span class="map-road road-two"></span><span class="map-road road-three"></span>
                    <span class="map-marker">●</span><span class="map-place" id="hero-map-place">{{ $featuredEvent?->location ?: 'Bolivia' }}</span><span class="map-link">Ver en mapa ↗</span>
                </a>
            </aside>
            @if($heroSlides->count() > 1)<button class="hero-arrow hero-arrow-right" type="button" data-slide-direction="1" aria-label="Siguiente evento">›</button>@endif
            @if($heroSlides->count() > 1)<div class="hero-dots" aria-label="Seleccionar evento destacado">
                @foreach($heroSlides as $index => $slide)<button type="button" class="{{ $index === 0 ? 'selected' : '' }}" data-slide-to="{{ $index }}" aria-label="Mostrar evento {{ $index + 1 }}"></button>@endforeach
            </div>@endif
        </section>

        <div class="dashboard-content">
            <section class="content-section upcoming-section" id="eventos">
                <div class="section-heading"><h2><span class="heading-mark">▦</span> Próximos eventos</h2><a class="see-all" href="{{ route('eventos.index') }}">Ver todos <span>→</span></a></div>
                <div class="event-grid">
                    @forelse($upcomingEvents->values() as $index => $event)
                        @php $cardImage = $event->cover_path ? url('/storage/'.$event->cover_path) : $fallbackCovers[$index % count($fallbackCovers)]; @endphp
                        <article class="event-card">
                            <a class="event-photo" href="{{ route('eventos.show', $event) }}" style="--card-image: url('{{ $cardImage }}')"><span class="status-badge {{ $event->status === 'en_curso' ? 'status-live' : 'status-next' }}">{{ $event->status === 'en_curso' ? 'En curso' : 'Próximo' }}</span></a>
                            <div class="event-card-body">
                                <a class="event-title" href="{{ route('eventos.show', $event) }}">{{ $event->title }}</a>
                                <div class="event-detail"><span class="red-mark">▦</span>{{ $event->event_date?->format('d M Y') }}</div>
                                <div class="event-detail"><span class="red-mark">●</span>{{ $event->location }}</div>
                                <div class="event-detail"><span class="red-mark">◉</span>{{ $event->categories->pluck('name')->join(', ') ?: ($event->category?->name ?: 'Categorías por anunciar') }}</div>
                                <div class="event-card-actions"><a class="button button-red" href="{{ route('eventos.show', $event) }}">Ver evento <span>→</span></a>
                                    @if($event->status === 'programado')<a class="button button-outline" href="{{ route('inscripciones.create', ['event_id' => $event->id]) }}">Inscribirme</a>@endif
                                </div>
                            </div>
                        </article>
                    @empty<div class="empty-state">Todavía no hay eventos programados.</div>@endforelse
                </div>
            </section>

            @if($upcomingStarts->isNotEmpty())
                <section class="starts-strip" aria-label="Próximas largadas"><div class="section-heading"><h2><span class="heading-mark">▤</span> Próximas largadas</h2></div><div class="starts-list">
                    @foreach($upcomingStarts->take(3) as $event)
                        @foreach($event->startingOrders->take(2) as $startingOrder)
                            <a class="start-item" href="{{ route('eventos.show', $event) }}"><strong>{{ $startingOrder->scheduled_time ? substr($startingOrder->scheduled_time, 0, 5) : 'Hora pendiente' }}</strong><span>{{ $event->title }} · {{ $startingOrder->category->name }}</span><b>→</b></a>
                        @endforeach
                    @endforeach
                </div></section>
            @endif

            <div class="dashboard-grid">
                <section class="panel finished-panel" id="campeonatos">
                    <div class="panel-heading"><h2><span class="heading-mark">⚑</span> Eventos finalizados</h2><a class="see-all" href="{{ route('resultados.index') }}">Ver todos <span>→</span></a></div>
                    <div class="finished-grid">
                        @forelse($finishedEvents as $event)
                            @php $finishImage = $event->cover_path ? url('/storage/'.$event->cover_path) : $fallbackCovers[$loop->index % count($fallbackCovers)]; @endphp
                            <a class="finished-card" href="{{ route('eventos.resultados', $event) }}"><span class="finished-photo" style="--card-image: url('{{ $finishImage }}')"></span><strong>{{ $event->title }}</strong><small>{{ $event->event_date?->format('d/m/Y') }} · {{ $event->location }}</small><span class="finished-link">♜ Ver resultados</span></a>
                        @empty<div class="empty-state">Aún no hay competencias finalizadas.</div>@endforelse
                    </div>
                </section>

                <section class="panel results-panel" id="resultados">
                    <div class="panel-heading"><h2><span class="heading-mark">♜</span> Últimos resultados</h2><a class="see-all" href="{{ route('resultados.index') }}">Ver todos <span>→</span></a></div>
                    <div class="results-table-wrap"><table class="results-table"><thead><tr><th>Evento / categoría</th><th>1.º</th><th>2.º</th><th>3.º</th></tr></thead><tbody>
                        @forelse($resultEvents as $event)
                            @php $topResults = $event->registrations->values(); @endphp
                            <tr><td><a href="{{ route('eventos.resultados', $event) }}"><strong>{{ $event->title }}</strong><small>{{ $event->event_date?->format('d/m/Y') }}</small></a></td>
                                @foreach(range(0, 2) as $position)<td>{{ $topResults->get($position)?->participant?->name ? \Illuminate\Support\Str::limit($topResults->get($position)->participant->name, 12) : '—' }}</td>@endforeach
                            </tr>
                        @empty<tr><td colspan="4" class="table-empty">Sin resultados publicados todavía.</td></tr>@endforelse
                    </tbody></table></div>
                </section>

                <section class="panel ranking-panel" id="ranking">
                    <div class="panel-heading"><h2><span class="heading-mark">♜</span> Ranking {{ now()->year }}</h2><a class="see-all" href="{{ route('resultados.index') }}">Ver completo <span>→</span></a></div>
                    <div class="ranking-table-wrap"><table class="ranking-table"><thead><tr><th>Pos.</th><th>Piloto</th><th>Puntos</th></tr></thead><tbody>
                        @forelse($ranking as $row)<tr><td><span class="rank-number rank-{{ $loop->iteration }}">{{ $loop->iteration }}</span></td><td>{{ $row->participant?->name ?: 'Piloto' }}</td><td><strong>{{ (int) $row->points }}</strong></td></tr>
                        @empty<tr><td colspan="3" class="table-empty">El ranking aparecerá cuando se publiquen resultados.</td></tr>@endforelse
                    </tbody></table></div>
                </section>
            </div>

            <div class="lower-grid">
                <section class="panel circuits-panel" id="circuitos">
                    <div class="panel-heading"><h2><span class="heading-mark green-mark">♟</span> Circuitos</h2><a class="see-all" href="{{ route('circuitos.index') }}">Ver todos <span>→</span></a></div>
                    <div class="circuit-list">
                        @forelse($circuits as $circuit)<a class="circuit-item" href="{{ route('circuitos.show', $circuit) }}">
                            @if($circuit->image_path)<img src="{{ url('/storage/'.$circuit->image_path) }}" alt="" loading="lazy">@else<span class="circuit-placeholder">⌁</span>@endif
                            <span><strong>{{ $circuit->name }}</strong><small>{{ $circuit->events_count }} {{ $circuit->events_count === 1 ? 'evento' : 'eventos' }}</small></span>
                        </a>@empty<div class="empty-state">Los circuitos registrados aparecerán aquí.</div>@endforelse
                    </div>
                </section>
                <section class="panel updates-panel">
                    <div class="panel-heading"><h2><span class="heading-mark green-mark">♧</span> Últimas novedades</h2><a class="see-all" href="{{ route('eventos.index') }}">Ver todos <span>→</span></a></div>
                    <div class="updates-list">
                        @forelse($upcomingEvents->take(3) as $event)<a class="update-item" href="{{ route('eventos.show', $event) }}"><span class="update-dot green-dot">●</span><span><strong>Nuevo evento · {{ $event->title }}</strong><small>{{ $event->event_date?->format('d/m/Y') }} · {{ $event->location }}</small></span><time>{{ $loop->first ? 'Próximo' : 'Agenda' }}</time></a>
                        @empty<div class="empty-state">No hay novedades por el momento.</div>@endforelse
                        @foreach($resultEvents->take(2) as $event)<a class="update-item" href="{{ route('eventos.resultados', $event) }}"><span class="update-dot red-dot">●</span><span><strong>Resultados publicados · {{ $event->title }}</strong><small>{{ $event->event_date?->format('d/m/Y') }}</small></span><time>Resultados</time></a>@endforeach
                    </div>
                </section>
            </div>

            <section class="category-strip" aria-label="Categorías"><strong>Categorías</strong>
                @forelse($categories as $category)<a href="{{ route('categorias.index') }}">{{ $category->name }}</a>@empty<a href="{{ route('categorias.index') }}">Explorar categorías</a>@endforelse
                <span class="category-stats">{{ $stats['participants'] }} pilotos · {{ $stats['events'] }} eventos · {{ $stats['categories'] }} categorías · {{ $stats['results'] }} resultados</span>
            </section>
        </div>
    </main>
    <footer class="site-footer"><a class="brand" href="{{ route('home') }}"><span class="brand-mountain" aria-hidden="true">▲</span><span class="brand-copy"><strong>MOTOCROSS</strong><b>BOLIVIA <i></i></b></span></a><span>La pasión del motocross, nuestro deporte <b class="bolivia-flag" aria-label="Bolivia"></b></span><small>© {{ now()->year }} Motocross Bolivia</small></footer>
</div>
<script>
    const heroSlides = @json($heroSlides);
    const hero = document.querySelector('.hero');
    const heroDots = [...document.querySelectorAll('[data-slide-to]')];
    let activeSlide = 0;
    const countdown = document.querySelector('[data-countdown]');
    let target = countdown?.dataset.countdown ? new Date(`${countdown.dataset.countdown}T00:00:00`).getTime() : 0;
    const updateCountdown = () => {
        if (!countdown || !target) return;
        const remaining = Math.max(0, target - Date.now());
        const units = {
            days: Math.floor(remaining / 86400000),
            hours: Math.floor((remaining % 86400000) / 3600000),
            minutes: Math.floor((remaining % 3600000) / 60000),
            seconds: Math.floor((remaining % 60000) / 1000),
        };
        Object.entries(units).forEach(([unit, value]) => {
            const element = countdown.querySelector(`[data-${unit}]`);
            if (element) element.textContent = String(value).padStart(2, '0');
        });
    };

    const showSlide = (index) => {
        if (!heroSlides.length) return;
        activeSlide = (index + heroSlides.length) % heroSlides.length;
        const slide = heroSlides[activeSlide];
        hero.style.setProperty('--hero-image', `url('${slide.image}')`);
        document.querySelector('#hero-title').textContent = slide.title;
        document.querySelector('#hero-date').textContent = slide.date || '';
        document.querySelector('#hero-location').textContent = slide.location;
        document.querySelector('#hero-event-link').href = slide.eventUrl;
        document.querySelector('#hero-registration-link').href = slide.registrationUrl;
        document.querySelector('#hero-map-link').href = slide.mapUrl;
        document.querySelector('#hero-map-place').textContent = slide.location;
        document.querySelector('#hero-categories').replaceChildren(...(slide.categories.length ? slide.categories : [slide.fallbackCategory]).map((name) => {
            const tag = document.createElement('span');
            tag.textContent = name;
            return tag;
        }));
        countdown.dataset.countdown = slide.dateIso || '';
        target = slide.dateIso ? new Date(`${slide.dateIso}T00:00:00`).getTime() : 0;
        heroDots.forEach((dot, dotIndex) => dot.classList.toggle('selected', dotIndex === activeSlide));
        updateCountdown();
    };

    document.querySelectorAll('[data-slide-direction]').forEach((button) => {
        button.addEventListener('click', () => showSlide(activeSlide + Number(button.dataset.slideDirection)));
    });
    heroDots.forEach((dot) => dot.addEventListener('click', () => showSlide(Number(dot.dataset.slideTo))));

    updateCountdown();
    window.setInterval(updateCountdown, 1000);
</script>
</body>
</html>