<x-app-layout>
    <div class="workspace-page">
        <header class="workspace-page-heading">
            <div><span class="workspace-eyebrow">{{ auth()->user()->role === 'piloto' ? 'Tu temporada' : 'Gestión' }}</span><h1>{{ auth()->user()->role === 'piloto' ? 'Mis inscripciones' : 'Inscripciones' }}</h1><p>{{ auth()->user()->role === 'piloto' ? 'Consulta el estado de tus pagos y competencias.' : 'Consulta y administra los pilotos inscritos.' }}</p></div>
            @if(in_array(auth()->user()->role, ['organizador', 'administrador']))
                <div class="workspace-heading-actions">
                    <a href="{{ route('inscripciones.export', request()->query()) }}" class="btn btn-outline-primary">↓ Exportar lista</a>
                    <a href="{{ route('inscripciones.create') }}" class="btn btn-primary">＋ Nueva inscripción</a>
                </div>
            @endif
        </header>

        @if(session('success'))<div class="alert alert-success mb-0" role="status">{{ session('success') }}</div>@endif

        @if(in_array(auth()->user()->role, ['organizador', 'administrador']))
        <form method="GET" action="{{ route('inscripciones.index') }}" class="workspace-filterbar">
            <label class="workspace-search-field"><span aria-hidden="true">⌕</span><input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o cédula..."></label>
            <select name="event_id" class="form-select" aria-label="Filtrar por evento">
                <option value="">Todos los eventos</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" @selected((string) request('event_id') === (string) $event->id)>{{ $event->title }}</option>
                @endforeach
            </select>
            <select name="category_id" class="form-select" aria-label="Filtrar por categoría">
                <option value="">Todas las categorías</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                @foreach(['pendiente' => 'Pendiente', 'confirmada' => 'Confirmada', 'rechazada' => 'Rechazada', 'cancelada' => 'Cancelada'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit">Filtrar</button>
            @if(request()->hasAny(['q', 'event_id', 'category_id', 'status']))
                <a href="{{ route('inscripciones.index') }}" class="btn btn-outline-secondary">Limpiar</a>
            @endif
        </form>
        @endif

        @if(auth()->user()->role === 'piloto')
            @php
                $activeRegistrations = $registrations->filter(fn ($registration) =>
                    in_array($registration->status, \App\Models\Inscripcion::ACTIVE_STATUSES, true)
                    && in_array($registration->event?->status, ['programado', 'en_curso'], true)
                );
                $registrationHistory = $registrations->reject(fn ($registration) =>
                    $activeRegistrations->contains('id', $registration->id)
                );
            @endphp
            <section class="pilot-my-registration-view" x-data="{ tab: 'active' }">
                <nav class="pilot-my-registration-tabs" role="tablist" aria-label="Filtrar mis inscripciones">
                    <button type="button" role="tab" :aria-selected="tab === 'active'" :class="{ 'is-active': tab === 'active' }" @click="tab = 'active'">Activas <span>{{ $activeRegistrations->count() }}</span></button>
                    <button type="button" role="tab" :aria-selected="tab === 'history'" :class="{ 'is-active': tab === 'history' }" @click="tab = 'history'">Historial <span>{{ $registrationHistory->count() }}</span></button>
                </nav>

                <div class="pilot-my-registration-list" x-show="tab === 'active'" role="tabpanel">
                    @forelse($activeRegistrations as $registration)
                        @php
                            $event = $registration->event;
                            $eventStatus = $event?->status === 'en_curso' ? 'En curso' : ($registration->status === 'confirmada' ? 'Confirmado' : 'Pendiente');
                        @endphp
                        <article class="pilot-my-registration-card">
                            @if($event?->cover_path)<img src="{{ url('/storage/'.$event->cover_path) }}" alt="Portada de {{ $event->title }}" loading="lazy">@else<span class="pilot-my-registration-placeholder" aria-hidden="true">🏁</span>@endif
                            <div class="pilot-my-registration-details">
                                <div class="pilot-my-registration-title"><h2>{{ $event?->title ?? 'Evento' }}</h2><span class="pilot-my-registration-status status-{{ $registration->status }}">{{ $eventStatus }}</span></div>
                                <p>▣ {{ $event?->event_date?->format('d/m/Y') }} <span>·</span> ◉ {{ $event?->location ?? 'Ubicación pendiente' }}</p>
                                <p>▤ {{ $registration->category?->name ?? 'Categoría pendiente' }}</p>
                                <small>Pago: <strong>{{ ucfirst(str_replace('_', ' ', $registration->payment_status ?? 'pendiente')) }}</strong>@if($registration->payment_submitted_at) · {{ $registration->payment_submitted_at->format('d/m/Y') }}@endif</small>
                                <div class="pilot-my-registration-actions">
                                    <a class="btn btn-primary" href="{{ route('piloto.inscripciones.show', $registration) }}">{{ $registration->payment_status === 'aprobado' ? 'Ver inscripción' : 'Ver pago' }} <span aria-hidden="true">→</span></a>
                                    @if($registration->payment_proof_path)<a class="pilot-my-proof-link" href="{{ route('piloto.inscripciones.payment-proof', $registration) }}" target="_blank" rel="noopener">Ver comprobante</a>@endif
                                    @if($registration->status === 'pendiente')<form action="{{ route('piloto.inscripciones.cancel', $registration) }}" method="POST" onsubmit="return confirm('¿Deseas cancelar esta inscripción?')">@csrf @method('DELETE')<button type="submit" class="pilot-my-cancel-button">Cancelar</button></form>@endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="pilot-my-registration-empty"><strong>No tienes inscripciones activas</strong><p>Busca un evento programado y reserva tu lugar.</p><a href="{{ route('eventos.index') }}">Ver eventos</a></div>
                    @endforelse
                </div>

                <div class="pilot-my-registration-list" x-show="tab === 'history'" x-cloak role="tabpanel">
                    @forelse($registrationHistory as $registration)
                        @php $event = $registration->event; @endphp
                        <article class="pilot-my-registration-card">
                            @if($event?->cover_path)<img src="{{ url('/storage/'.$event->cover_path) }}" alt="Portada de {{ $event->title }}" loading="lazy">@else<span class="pilot-my-registration-placeholder" aria-hidden="true">🏁</span>@endif
                            <div class="pilot-my-registration-details">
                                <div class="pilot-my-registration-title"><h2>{{ $event?->title ?? 'Evento' }}</h2><span class="pilot-my-registration-status status-history">{{ $event?->status === 'finalizado' ? 'Finalizado' : ucfirst($registration->status) }}</span></div>
                                <p>▣ {{ $event?->event_date?->format('d/m/Y') }} <span>·</span> ◉ {{ $event?->location ?? 'Ubicación pendiente' }}</p>
                                <p>▤ {{ $registration->category?->name ?? 'Categoría pendiente' }}</p>
                                <div class="pilot-my-registration-actions"><a class="pilot-my-proof-link" href="{{ route('piloto.inscripciones.show', $registration) }}">Ver inscripción</a>
                                    @if($event?->status === 'finalizado' && $event->results_published)<a class="pilot-my-proof-link" href="{{ route('eventos.resultados', $event) }}">Ver resultados</a>@endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="pilot-my-registration-empty"><strong>Tu historial está vacío</strong><p>Aquí aparecerán las competencias finalizadas.</p></div>
                    @endforelse
                </div>
            </section>
        @else
        <section class="workspace-panel workspace-registration-table-panel">
            <div class="workspace-table-heading"><h2>Lista de pilotos inscritos</h2><span>Mostrando {{ $registrations->count() }} {{ $registrations->count() === 1 ? 'inscripción' : 'inscripciones' }}</span></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 workspace-registration-table">
                    <thead><tr><th>#</th><th>Nombre</th><th>Cédula</th><th>Evento</th><th>Categoría</th><th>Comprobante</th><th>Estado</th><th>Fecha de inscripción</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        @forelse($registrations as $registration)
                            <tr>
                                <td class="text-muted">{{ $loop->iteration }}</td>
                                <td><strong>{{ $registration->participant->name ?? 'Participante' }}</strong></td>
                                <td>{{ $registration->participant->document ?? '—' }}</td>
                                <td>{{ $registration->event->title ?? 'Evento' }}</td>
                                <td>{{ $registration->category->name ?? '—' }}</td>
                                <td>
                                    @if($registration->payment_proof_path)
                                        <a class="workspace-proof-link" href="{{ route(in_array(auth()->user()->role, ['organizador', 'administrador']) ? 'inscripciones.edit' : 'piloto.inscripciones.show', $registration) }}">▧ Ver</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($registration->status) {
                                            'confirmada' => 'workspace-status-success',
                                            'rechazada', 'cancelada' => 'workspace-status-danger',
                                            default => 'workspace-status-pending',
                                        };
                                    @endphp
                                    <span class="workspace-status-pill {{ $statusClass }}">{{ ucfirst($registration->status) }}</span>
                                    @if($registration->payment_status === 'aprobado')<small class="workspace-payment-state">Pagado</small>@elseif($registration->payment_status === 'en_revision')<small class="workspace-payment-state">Pago pendiente</small>@endif
                                </td>
                                <td>{{ $registration->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    @if(in_array(auth()->user()->role, ['organizador', 'administrador']))
                                        <div class="workspace-row-actions">
                                            <a href="{{ route('inscripciones.edit', $registration) }}" class="workspace-icon-button" title="Ver y revisar inscripción" aria-label="Ver y revisar inscripción">⌕</a>
                                            <div class="dropdown">
                                                <button class="workspace-icon-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Más acciones" aria-label="Más acciones">⋮</button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="{{ route('inscripciones.edit', $registration) }}">Ver / editar</a>
                                                    @if($registration->status === 'pendiente')
                                                        <form action="{{ route('inscripciones.update', $registration) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="status" value="confirmada"><button class="dropdown-item" type="submit">Aceptar inscripción</button></form>
                                                        <form action="{{ route('inscripciones.update', $registration) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="status" value="rechazada"><button class="dropdown-item text-danger" type="submit">Rechazar inscripción</button></form>
                                                    @endif
                                                    <div class="dropdown-divider"></div>
                                                    <form action="{{ route('inscripciones.destroy', $registration) }}" method="POST" onsubmit="return confirm('¿Deseas eliminar esta inscripción?')">@csrf @method('DELETE')<button class="dropdown-item text-danger" type="submit">Eliminar</button></form>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <a href="{{ route('piloto.inscripciones.show', $registration) }}" class="workspace-icon-button" title="Ver detalles" aria-label="Ver detalles">⌕</a>
                                        @if($registration->status === 'pendiente')
                                            <form action="{{ route('piloto.inscripciones.cancel', $registration) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Deseas cancelar esta inscripción?')">@csrf @method('DELETE')<button type="submit" class="workspace-icon-button" title="Cancelar inscripción" aria-label="Cancelar inscripción">×</button></form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="workspace-empty-cell">No hay inscripciones que coincidan con estos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @endif
    </div>
</x-app-layout>
