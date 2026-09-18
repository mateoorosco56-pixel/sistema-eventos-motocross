<x-app-layout>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-primary mb-2">Evento</p>
                <h2 class="fw-bold mb-1">Resultados del evento</h2>
                <p class="mb-0 text-muted">{{ $event->title }}</p>
            </div>
            <a href="{{ route('eventos.index') }}" class="btn btn-outline-primary">Volver</a>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body pb-3">
                <div class="row g-3 align-items-center">
                    <div class="col-md-8">
                        <span class="d-block text-uppercase small fw-bold text-secondary mb-1">Competición</span>
                        <h3 class="fw-bold text-dark mb-1">{{ $event->title }}</h3>
                        <div class="small text-dark">
                            <div class="mb-1">📅 {{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }} · 📍 {{ $event->location }}</div>
                            <div class="mb-1">📝 {{ $event->description ? Str::limit($event->description, 100) : 'Sin descripción' }}</div>
                            @if($event->categories->count())
                                <div>🏷️ Categorías: {{ $event->categories->pluck('name')->implode(', ') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        @if($event->status === 'finalizado' && ! $event->results_published)
                            <span class="badge bg-warning text-dark fs-6 px-3 py-2">RESULTADOS PENDIENTES</span>
                        @elseif($event->results_published)
                            <span class="badge bg-success fs-6 px-3 py-2">RESULTADOS PUBLICADOS</span>
                        @else
                            <span class="badge bg-info fs-6 px-3 py-2">EN EDICIÓN</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($event->status === 'finalizado' && ! $event->results_published)
            <div class="alert alert-warning border-0 shadow-sm">
                <strong>⏳ Los resultados todavía no han sido publicados</strong>
                <div class="mb-0 mt-2">por el organizador.</div>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Participante</th>
                                <th>Posición</th>
                                <th>Puntos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($registrations as $registration)
                                <tr>
                                    <td>{{ $registration->participant->name ?? 'Participante' }}</td>
                                    <td>{{ $registration->result_position ?: '-' }}</td>
                                    <td>{{ $registration->result_points ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        @if($event->status === 'finalizado' && ! $event->results_published)
                                            Los resultados todavía no han sido publicados por el organizador.
                                        @else
                                            No hay resultados para este evento.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
