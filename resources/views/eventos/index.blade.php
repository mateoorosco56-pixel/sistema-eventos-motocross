<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-warning mb-2">Gestión</p>
                <h2 class="fw-bold mb-1">{{ auth()->check() && auth()->user()->role === 'organizador' ? 'Mis eventos' : 'Eventos' }}</h2>
                <p class="mb-0 text-muted">{{ auth()->check() && auth()->user()->role === 'organizador' ? 'Eventos que organizas y sus pilotos inscritos' : 'Lista de eventos registrados' }}</p>
            </div>
            @if(auth()->check() && in_array(auth()->user()->role, ['organizador', 'administrador']))
                <a href="{{ route('eventos.create') }}" class="btn btn-dark">+ Nuevo evento</a>
            @endif
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(auth()->check() && auth()->user()->role === 'piloto')
            @php
                $currentFilter = request('status', 'todos');
                $eventFilters = [
                    'todos' => 'Todos',
                    'proximos' => 'Próximos',
                    'en_curso' => 'En curso',
                    'finalizados' => 'Finalizados',
                ];
                $eventFilterBaseQuery = request()->except('status');
                $eventFilterUrls = [
                    'todos' => route('eventos.index', $eventFilterBaseQuery),
                    'proximos' => route('eventos.index', array_merge($eventFilterBaseQuery, ['status' => 'proximos'])),
                    'en_curso' => route('eventos.index', array_merge($eventFilterBaseQuery, ['status' => 'en_curso'])),
                    'finalizados' => route('eventos.index', array_merge($eventFilterBaseQuery, ['status' => 'finalizados'])),
                ];
            @endphp
            <div class="pilot-event-browser-heading"><h2>Eventos para pilotos</h2><span>{{ $events->count() }} eventos</span></div>
            <form method="GET" action="{{ route('eventos.index') }}" class="pilot-event-search">
                <label for="pilot-event-search">Buscar evento</label>
                <input id="pilot-event-search" type="search" name="q" value="{{ request('q') }}" placeholder="Nombre, circuito o categoría">
                @if(request()->filled('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
                <input type="hidden" name="status" value="{{ $currentFilter }}">
                <button type="submit" aria-label="Buscar eventos">⌕</button>
            </form>
            <nav class="pilot-event-filter-tabs" aria-label="Filtrar eventos">
                @foreach($eventFilters as $filterKey => $filterLabel)
                    <a href="{{ $eventFilterUrls[$filterKey] }}" @class(['is-active' => $currentFilter === $filterKey]) aria-current="{{ $currentFilter === $filterKey ? 'page' : 'false' }}">{{ $filterLabel }}</a>
                @endforeach
            </nav>
            <div class="row g-4">
                @forelse ($events as $event)
                    <div class="col-md-6 col-xl-4">
                        <article class="card pilot-event-card border-0 shadow-sm h-100">
                            @include('eventos.partials.public-cover', ['event' => $event])
                            <div class="card-body d-flex flex-column">
                                <div class="pilot-event-card-title"><div><span class="pilot-event-status status-{{ $event->status }}">{{ $event->status === 'en_curso' ? 'En curso' : ($event->status === 'finalizado' ? 'Finalizado' : 'Próximo') }}</span><h3>{{ $event->title }}</h3></div></div>
                                <div class="pilot-event-card-meta"><span>▣ {{ $event->event_date?->format('d/m/Y') }}</span><span>◉ {{ $event->location }}</span><span>▤ {{ $event->categories->pluck('name')->join(', ') ?: ($event->category?->name ?: 'Categorías por definir') }}</span></div>
                                @include('eventos.partials.category-fees-display', ['event' => $event])
                                <p class="pilot-event-card-description">{{ Str::limit($event->description ?: 'Evento disponible para pilotos registrados.', 110) }}</p>
                                <div class="pilot-event-card-actions">
                                    <a href="{{ route('eventos.show', $event) }}" class="btn btn-outline-primary">Ver evento</a>
                                    @if($event->status === 'programado')<a href="{{ route('inscripciones.create', ['event_id' => $event->id]) }}" class="btn btn-primary">Inscribirse</a>
                                    @elseif($event->status === 'finalizado' && $event->results_published)<a href="{{ route('eventos.resultados', $event) }}" class="btn btn-primary">Resultados</a>@endif
                                </div>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-light border text-center py-4">
                            No hay eventos en esta categoría.
                        </div>
                    </div>
                @endforelse
            </div>
        @elseif(auth()->check() && in_array(auth()->user()->role, ['organizador', 'administrador']))
            @php
                $eventGroups = [
                    'Próximos' => $events->where('status', 'programado'),
                    'En curso' => $events->where('status', 'en_curso'),
                    'Finalizados' => $events->where('status', 'finalizado'),
                ];
            @endphp

            @foreach ($eventGroups as $groupTitle => $groupEvents)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0">{{ $groupTitle }}</h5>
                    </div>
                    <div class="card-body p-0">
                        @if($groupEvents->isEmpty())
                            <div class="px-4 py-3 text-muted">No hay eventos en esta sección.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Evento</th>
                                            <th>Fecha</th>
                                            <th>Ubicación</th>
                                            <th>Pilotos inscritos</th>
                                            <th>Estado</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($groupEvents as $event)
                                            <tr>
                                                <td>
                                                    {{ $event->title }}
                                                    @if(auth()->check() && auth()->user()->unreadNotifications->filter(fn ($notification) => ($notification->data['event_id'] ?? null) == $event->id)->count())
                                                        <span class="badge bg-danger">{{ auth()->user()->unreadNotifications->filter(fn ($notification) => ($notification->data['event_id'] ?? null) == $event->id)->count() }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</td>
                                                <td>{{ $event->location }}</td>
                                                <td><strong>{{ $event->active_registrations_count }}</strong></td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        {{ ucfirst(str_replace('_', ' ', $event->status)) }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="{{ route('eventos.edit', $event) }}" class="btn btn-sm btn-outline-dark">Editar</a>
                                                    <form action="{{ route('eventos.destroy', $event) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Deseas eliminar este evento?')">Eliminar</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div class="row g-4">
                @forelse ($events as $event)
                    <div class="col-md-6 col-xl-4">
                        <div class="card border-0 shadow-sm h-100">
                            @include('eventos.partials.public-cover', ['event' => $event])
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="w-100">
                                        <span class="badge bg-primary-subtle text-primary mb-2">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                                        <h4 class="fw-bold mb-0">{{ $event->title }}</h4>
                                    </div>
                                </div>

                                <div class="text-muted small mb-3">
                                    <div class="mb-1"><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</div>
                                    <div class="mb-1"><strong>Ubicación:</strong> {{ $event->location }}</div>
                                </div>

                                @include('eventos.partials.category-fees-display', ['event' => $event])

                                <p class="text-muted flex-grow-1">
                                    {{ Str::limit($event->description ?: 'Evento disponible para usuarios registrados.', 120) }}
                                </p>

                                <div class="d-grid gap-2 mt-3">
                                    <a href="{{ route('eventos.show', $event) }}" class="btn btn-outline-primary">Ver a fondo</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-light border text-center py-4">
                            No hay eventos disponibles.
                        </div>
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</x-app-layout>
