<x-app-layout>
    @php
        $sections = [
            ['title' => 'Próximos', 'items' => $upcomingEvents ?? collect(), 'state' => 'Próximo', 'key' => 'proximos'],
            ['title' => 'En curso', 'items' => $activeEvents ?? collect(), 'state' => 'En curso', 'key' => 'activos'],
            ['title' => 'Finalizados', 'items' => $finishedEvents ?? collect(), 'state' => 'Finalizado', 'key' => 'finalizados'],
        ];
        $totalEvents = collect($sections)->sum(fn ($section) => $section['items']->count());
    @endphp

    <div class="container py-4 organizer-events-page">
        <header class="organizer-events-heading">
            <div>
                <span class="organizer-events-eyebrow">Panel del organizador</span>
                <h1>Mis eventos</h1>
                <p>Administra tu calendario de competiciones desde un solo lugar.</p>
            </div>
            <a href="{{ route('eventos.create') }}" class="btn organizer-create-event"><span aria-hidden="true">+</span> Crear evento</a>
        </header>

        <div class="organizer-event-stats" aria-label="Resumen de eventos">
            <div class="organizer-event-stat organizer-event-stat-total"><span>Total de eventos</span><strong>{{ $totalEvents }}</strong><small>En tu calendario</small></div>
            <div class="organizer-event-stat organizer-event-stat-upcoming"><span>Próximos</span><strong>{{ ($upcomingEvents ?? collect())->count() }}</strong><small>Pendientes de realizar</small></div>
            <div class="organizer-event-stat organizer-event-stat-active"><span>En curso</span><strong>{{ ($activeEvents ?? collect())->count() }}</strong><small>Competencias activas</small></div>
            <div class="organizer-event-stat organizer-event-stat-finished"><span>Finalizados</span><strong>{{ ($finishedEvents ?? collect())->count() }}</strong><small>En el historial</small></div>
        </div>

        <div x-data="{ activeFilter: 'all' }" class="organizer-events-workspace">
            <div class="organizer-event-filters" aria-label="Filtrar eventos" role="group">
                <button type="button" :aria-pressed="activeFilter === 'all'" :class="activeFilter === 'all' ? 'is-active' : ''" class="organizer-event-filter" @click="activeFilter = 'all'">Todos <span>{{ $totalEvents }}</span></button>
                <button type="button" :aria-pressed="activeFilter === 'proximos'" :class="activeFilter === 'proximos' ? 'is-active' : ''" class="organizer-event-filter" @click="activeFilter = 'proximos'">Próximos <span>{{ ($upcomingEvents ?? collect())->count() }}</span></button>
                <button type="button" :aria-pressed="activeFilter === 'activos'" :class="activeFilter === 'activos' ? 'is-active' : ''" class="organizer-event-filter" @click="activeFilter = 'activos'">En curso <span>{{ ($activeEvents ?? collect())->count() }}</span></button>
                <button type="button" :aria-pressed="activeFilter === 'finalizados'" :class="activeFilter === 'finalizados' ? 'is-active' : ''" class="organizer-event-filter" @click="activeFilter = 'finalizados'">Finalizados <span>{{ ($finishedEvents ?? collect())->count() }}</span></button>
            </div>

            @foreach ($sections as $section)
                <section class="organizer-events-section organizer-events-{{ $section['key'] }}" x-show="activeFilter === 'all' || activeFilter === '{{ $section['key'] }}'" x-transition>
                    <header class="organizer-events-section-heading">
                        <div class="organizer-section-marker" aria-hidden="true"></div>
                        <div><h2>{{ $section['title'] }}</h2><p>{{ $section['items']->count() }} {{ $section['items']->count() === 1 ? 'evento' : 'eventos' }}</p></div>
                        <span class="organizer-section-total">{{ str_pad((string) $section['items']->count(), 2, '0', STR_PAD_LEFT) }}</span>
                    </header>

                    @if($section['items']->isEmpty())
                        <div class="organizer-events-empty"><span aria-hidden="true">0</span><div><strong>Sin eventos en esta sección</strong><p>Cuando tengas eventos {{ strtolower($section['title']) }}, aparecerán aquí.</p></div></div>
                    @else
                        <div class="table-responsive organizer-events-table-wrap">
                            <table class="table align-middle mb-0 organizer-events-table">
                                <thead>
                                    <tr><th>Evento</th><th>Fecha</th><th>Ubicación</th><th class="text-center">Pilotos inscritos</th><th class="text-center">Aceptados</th><th class="text-end">Acciones</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($section['items'] as $event)
                                        <tr>
                                            <td><div class="organizer-event-name"><strong>{{ $event->title }}</strong><span class="organizer-event-status">{{ $section['state'] }}</span></div></td>
                                            <td><time datetime="{{ \Carbon\Carbon::parse($event->event_date)->toDateString() }}">{{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</time></td>
                                            <td><span class="organizer-event-location">{{ $event->location }}</span></td>
                                            <td class="text-center"><span class="organizer-event-count">{{ $event->active_registrations_count }}</span></td>
                                            <td class="text-center"><span class="organizer-event-count organizer-event-count-accepted">{{ $event->confirmed_registrations_count }}</span></td>
                                            <td class="text-end">
                                                <div class="organizer-event-actions">
                                                    <a href="{{ route('eventos.show', $event) }}" class="btn btn-sm organizer-event-action" title="Ver detalles de {{ $event->title }}">Ver</a>
                                                    <a href="{{ route('eventos.edit', $event) }}" class="btn btn-sm organizer-event-action" title="Editar {{ $event->title }}">Editar</a>
                                                    @if($section['title'] === 'Finalizados')
                                                        <a href="{{ route('resultados.index') }}" class="btn btn-sm organizer-event-action organizer-event-action-results" title="Gestionar resultados de {{ $event->title }}">Resultados</a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>

        <footer class="organizer-events-quick-links">
            <span>Accesos directos</span>
            <a href="{{ route('inscripciones.index') }}">Revisar inscripciones</a>
            <a href="{{ route('resultados.index') }}">Gestionar resultados</a>
        </footer>
    </div>
</x-app-layout>
