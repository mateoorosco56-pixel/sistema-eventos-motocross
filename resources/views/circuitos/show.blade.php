<x-app-layout>
    <div class="container-fluid py-4">
        <a href="{{ route('circuitos.index') }}" class="btn btn-sm btn-outline-secondary mb-3">← Todos los circuitos</a>

        <section class="card border-0 shadow-sm mb-4 overflow-hidden">
            @if($circuit->image_path)
                <img src="{{ url('/storage/'.$circuit->image_path) }}" alt="Foto de {{ $circuit->name }}" class="public-circuit-hero-image">
            @endif
            <div class="card-body p-4">
                <p class="text-uppercase small fw-semibold text-success mb-2">Circuito</p>
                <h1 class="fw-bold mb-1">{{ $circuit->name }}</h1>
                <p class="text-muted mb-0">Publicado por {{ $circuit->organizer?->name ?? 'Organizador' }}</p>
            </div>
        </section>

        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="h4 fw-bold mb-1">Eventos en este circuito</h2>
                <p class="text-muted small mb-0">{{ $events->count() }} {{ $events->count() === 1 ? 'evento publicado' : 'eventos publicados' }}</p>
            </div>
        </div>

        <div class="row g-4">
            @forelse($events as $event)
                <div class="col-md-6 col-xl-4">
                    <article class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <span class="badge text-bg-light align-self-start mb-2">{{ $event->status === 'en_curso' ? 'En curso' : ($event->status === 'finalizado' ? 'Finalizado' : 'Próximo') }}</span>
                            <h3 class="h5 fw-bold">{{ $event->title }}</h3>
                            <p class="small text-muted mb-2">{{ $event->event_date?->format('d/m/Y') }} · {{ $event->location }}</p>
                            <p class="small text-muted mb-3">{{ $event->categories->pluck('name')->join(', ') ?: ($event->category?->name ?? 'Categorías por definir') }}</p>
                            <a href="{{ route('eventos.show', $event) }}" class="btn btn-outline-primary mt-auto">Ver evento</a>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border text-center py-4">Este circuito todavía no tiene eventos publicados.</div>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>