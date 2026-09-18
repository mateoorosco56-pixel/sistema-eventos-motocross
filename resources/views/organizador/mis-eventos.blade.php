<x-app-layout>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-warning mb-2">Organizador</p>
                <h2 class="fw-bold mb-1">Mis eventos</h2>
                <p class="mb-0 text-muted">Administra tu calendario de competiciones desde un panel centralizado.</p>
            </div>
            <a href="{{ route('eventos.create') }}" class="btn btn-warning text-dark fw-semibold">+ Crear evento</a>
        </div>

        <div x-data="{ activeFilter: 'all' }">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" @click="activeFilter = 'all'" :class="activeFilter === 'all' ? 'badge bg-warning text-dark' : 'badge bg-light text-dark border'" class="px-3 py-2" style="cursor: pointer; border: none; font-weight: 500;">Todos</button>
                        <button type="button" @click="activeFilter = 'proximos'" :class="activeFilter === 'proximos' ? 'badge bg-warning text-dark' : 'badge bg-light text-dark border'" class="px-3 py-2" style="cursor: pointer; border: none; font-weight: 500;">Próximos</button>
                        <button type="button" @click="activeFilter = 'activos'" :class="activeFilter === 'activos' ? 'badge bg-warning text-dark' : 'badge bg-light text-dark border'" class="px-3 py-2" style="cursor: pointer; border: none; font-weight: 500;">En curso</button>
                        <button type="button" @click="activeFilter = 'finalizados'" :class="activeFilter === 'finalizados' ? 'badge bg-warning text-dark' : 'badge bg-light text-dark border'" class="px-3 py-2" style="cursor: pointer; border: none; font-weight: 500;">Finalizados</button>
                    </div>
                </div>
            </div>

            @php
                $sections = [
                    ['title' => 'Próximos', 'items' => $upcomingEvents ?? collect(), 'color' => 'warning', 'state' => 'PRÓXIMO', 'key' => 'proximos'],
                    ['title' => 'En curso', 'items' => $activeEvents ?? collect(), 'color' => 'primary', 'state' => 'EN CURSO', 'key' => 'activos'],
                    ['title' => 'Finalizados', 'items' => $finishedEvents ?? collect(), 'color' => 'secondary', 'state' => 'FINALIZADO', 'key' => 'finalizados'],
                ];
            @endphp

            @foreach ($sections as $section)
                <div class="card border-0 shadow-sm mb-4" x-show="activeFilter === 'all' || activeFilter === '{{ $section['key'] }}'" x-transition>
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">{{ $section['title'] }}</h5>
                </div>
                <div class="card-body">
                    @if($section['items']->isEmpty())
                        <div class="text-center text-muted py-4">
                            <p class="mb-0">No hay eventos en esta sección por el momento.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Evento</th>
                                        <th>Fecha</th>
                                        <th>Ubicación</th>
                                        <th class="text-center">Inscritos</th>
                                        <th class="text-center">Aceptados</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($section['items'] as $event)
                                        @php
                                            $totalRegistrations = $event->registrations->count();
                                            $confirmedRegistrations = $event->registrations->where('status', 'confirmada')->count();
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ $event->title }}</strong>
                                                <span class="badge bg-{{ $section['color'] }} text-{{ $section['color'] === 'warning' ? 'dark' : 'white' }} ms-2">{{ $section['state'] }}</span>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</td>
                                            <td>{{ $event->location }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark">{{ $totalRegistrations }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success-subtle text-success">{{ $confirmedRegistrations }}</span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('eventos.show', $event) }}" class="btn btn-outline-dark" title="Ver detalles">👁️</a>
                                                    <a href="{{ route('eventos.edit', $event) }}" class="btn btn-outline-warning" title="Editar">✏️</a>
                                                    @if($section['title'] === 'Finalizados')
                                                        <a href="{{ route('resultados.index') }}" class="btn btn-outline-success" title="Gestionar resultados">🏆</a>
                                                    @endif
                                                </div>
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
        </div>

        <div class="card border-0 shadow-sm bg-light">
            <div class="card-body text-center">
                <p class="text-muted mb-0">
                    <small>
                        💡 Acceso rápido: ve a <a href="{{ route('inscripciones.index') }}" class="text-decoration-none">Inscripciones</a> para revisar solicitudes,
                        o a <a href="{{ route('resultados.index') }}" class="text-decoration-none">Resultados</a> para actualizar posiciones.
                    </small>
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
