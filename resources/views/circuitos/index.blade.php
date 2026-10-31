<x-app-layout>
    <div class="container-fluid py-4">
        <div class="mb-4">
            <p class="text-uppercase small fw-semibold text-success mb-2">Instalaciones</p>
            <h1 class="fw-bold mb-1">Circuitos</h1>
            <p class="text-muted mb-0">Circuitos compartidos por los organizadores.</p>
        </div>

        <div class="row g-4">
            @forelse($circuits as $circuit)
                <div class="col-sm-6 col-xl-4">
                    <a href="{{ route('circuitos.show', $circuit) }}" class="card h-100 border-0 shadow-sm text-decoration-none public-circuit-card">
                        @if($circuit->image_path)
                            <img src="{{ url('/storage/'.$circuit->image_path) }}" alt="Foto de {{ $circuit->name }}" class="public-circuit-card-image">
                        @else
                            <div class="public-circuit-card-placeholder" aria-hidden="true">🏁</div>
                        @endif
                        <div class="card-body">
                            <h2 class="h5 fw-bold text-dark mb-1">{{ $circuit->name }}</h2>
                            <p class="small text-muted mb-0">
                                {{ $circuit->organizer?->name ?? 'Organizador' }}
                                <span aria-hidden="true">·</span>
                                {{ $circuit->events_count }} {{ $circuit->events_count === 1 ? 'evento' : 'eventos' }}
                            </p>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border text-center py-4">Los circuitos aparecerán aquí cuando un organizador los comparta.</div>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>