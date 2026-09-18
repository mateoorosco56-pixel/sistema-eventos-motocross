<x-app-layout>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-primary mb-2">Ranking</p>
                <h2 class="fw-bold mb-1">Ranking del evento</h2>
                <p class="mb-0 text-muted">{{ $event->title }}</p>
            </div>
            <a href="{{ route('piloto.mis-eventos') }}" class="btn btn-outline-primary">Volver</a>
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
                        <span class="badge bg-primary fs-6 px-3 py-2">CLASIFICACIÓN</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Posición</th>
                                <th>Piloto</th>
                                <th>Puntos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ranking as $item)
                                <tr>
                                    <td>#{{ $item['position'] }}</td>
                                    <td>{{ $item['participant'] }}</td>
                                    <td>{{ $item['points'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">Aún no hay ranking disponible para este evento.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
