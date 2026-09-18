<x-app-layout>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-primary mb-2">Piloto</p>
                <h2 class="fw-bold mb-1">Mis eventos</h2>
                <p class="mb-0 text-muted">Consulta tu historial, eventos activos y resultados finales.</p>
            </div>
            <a href="{{ route('eventos.index') }}" class="btn btn-primary">Ver eventos</a>
        </div>

        <div x-data="{ activeFilter: 'all' }">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" @click="activeFilter = 'all'" :class="activeFilter === 'all' ? 'badge bg-primary text-white' : 'badge bg-light text-dark border'" class="px-3 py-2" style="cursor: pointer; border: none; font-weight: 500;">Todos</button>
                        <button type="button" @click="activeFilter = 'proximos'" :class="activeFilter === 'proximos' ? 'badge bg-primary text-white' : 'badge bg-light text-dark border'" class="px-3 py-2" style="cursor: pointer; border: none; font-weight: 500;">Próximos</button>
                        <button type="button" @click="activeFilter = 'activos'" :class="activeFilter === 'activos' ? 'badge bg-primary text-white' : 'badge bg-light text-dark border'" class="px-3 py-2" style="cursor: pointer; border: none; font-weight: 500;">En curso</button>
                        <button type="button" @click="activeFilter = 'finalizados'" :class="activeFilter === 'finalizados' ? 'badge bg-primary text-white' : 'badge bg-light text-dark border'" class="px-3 py-2" style="cursor: pointer; border: none; font-weight: 500;">Finalizados</button>
                    </div>
                </div>
            </div>

            @php
                $sections = [
                    ['title' => 'Próximos', 'items' => $upcomingEvents ?? collect(), 'color' => 'primary', 'state' => 'PRÓXIMO', 'key' => 'proximos'],
                    ['title' => 'En curso', 'items' => $activeEvents ?? collect(), 'color' => 'warning', 'state' => 'EN CURSO', 'key' => 'activos'],
                    ['title' => 'Finalizados', 'items' => $finishedEvents ?? collect(), 'color' => 'secondary', 'state' => 'EVENTO FINALIZADO', 'key' => 'finalizados'],
                ];
            @endphp

            @foreach ($sections as $section)
                <div class="card border-0 shadow-sm mb-4" x-show="activeFilter === 'all' || activeFilter === '{{ $section['key'] }}'" x-transition>
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">{{ $section['title'] }}</h5>
                </div>
                <div class="card-body">
                    @if($section['items']->isEmpty())
                        <div class="text-center text-muted py-3">
                            No tienes eventos en esta sección por el momento.
                        </div>
                    @else
                        <div class="row g-3">
                            @foreach ($section['items'] as $participation)
                                @php $event = $participation->event; @endphp
                                <div class="col-lg-6">
                                    <div class="border rounded-4 h-100 p-3 shadow-sm bg-light-subtle">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="fw-bold mb-1">{{ $event->title ?? 'Evento' }}</h5>
                                                <div class="small text-muted">{{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }} · {{ $event->location ?? '-' }}</div>
                                            </div>
                                            <span class="badge bg-{{ $section['color'] }} text-white">{{ $section['state'] }}</span>
                                        </div>

                                        <div class="small text-muted mb-3">
                                            <div><strong>Inscripción:</strong> {{ $event->entry_fee !== null ? '$'.number_format($event->entry_fee, 2) : 'No especificada' }}</div>
                                            <div><strong>Cupo:</strong> {{ $event->max_participants ?? 'Sin límite especificado' }}</div>
                                            <div><strong>Límite de inscripción:</strong> {{ $event->registration_deadline ? \Carbon\Carbon::parse($event->registration_deadline)->format('d/m/Y') : 'No especificada' }}</div>
                                        </div>

                                        @if($section['title'] === 'Finalizados')
                                            <div class="mb-3">
                                                @if($event->results_published)
                                                    <span class="badge bg-success-subtle text-success">✓ Resultados publicados</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning">⏳ Los resultados todavía no han sido publicados</span>
                                                @endif
                                            </div>
                                        @endif

                                        <div class="d-flex gap-2 flex-wrap">
                                            @if($section['title'] === 'Finalizados' && $event->results_published)
                                                <a href="{{ route('eventos.resultados', $event) }}" class="btn btn-sm btn-outline-primary">Ver resultados</a>
                                            @endif
                                            <a href="{{ route('eventos.show', $event) }}" class="btn btn-sm btn-outline-dark">Ver detalles</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
        </div>
    </div>
</x-app-layout>
