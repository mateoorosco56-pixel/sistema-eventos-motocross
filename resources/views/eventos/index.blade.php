<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-warning mb-2">Gestión</p>
                <h2 class="fw-bold mb-1">Eventos</h2>
                <p class="mb-0 text-muted">Lista de eventos registrados</p>
            </div>
            @if(auth()->check() && in_array(auth()->user()->role, ['organizador', 'administrador']))
                <a href="{{ route('eventos.create') }}" class="btn btn-dark">+ Nuevo evento</a>
            @endif
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(auth()->check() && auth()->user()->role === 'piloto')
            <div class="mb-4">
                <h3 class="fw-bold mb-0">Eventos programados</h3>
            </div>
            <div class="row g-4">
                @forelse ($events as $event)
                    <div class="col-md-6 col-xl-4">
                        <div class="card border-0 shadow-sm h-100">
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
