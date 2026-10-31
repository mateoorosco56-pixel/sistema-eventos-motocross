<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-primary mb-2">Piloto</p>
                <h2 class="fw-bold mb-1">Eventos programados</h2>
                <p class="mb-0 text-muted">Elige el evento que te interesa y revisa todos los detalles antes de inscribirte.</p>
            </div>
            <a href="{{ route('eventos.index') }}" class="btn btn-outline-primary">Volver</a>
        </div>

        <div class="row g-4">
            @forelse ($events as $event)
                <div class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        @include('eventos.partials.public-cover', ['event' => $event])
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-primary-subtle text-primary mb-2">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                                    <h4 class="fw-bold mb-0">{{ $event->title }}</h4>
                                </div>
                            </div>

                            <div class="text-muted small mb-3">
                                <div class="mb-1"><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</div>
                                <div class="mb-1"><strong>Ubicación:</strong> {{ $event->location }}</div>
                            </div>

                            <p class="text-muted flex-grow-1">
                                {{ Str::limit($event->description ?: 'Evento disponible para pilotos registrados.', 120) }}
                            </p>

                            <div class="d-grid gap-2 mt-3">
                                <a href="{{ route('eventos.show', $event) }}" class="btn btn-outline-primary">Ver a fondo</a>
                                <a href="{{ route('inscripciones.create') }}?event_id={{ $event->id }}" class="btn btn-primary">Registrarse</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border text-center py-4">
                        No hay eventos programados disponibles.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
