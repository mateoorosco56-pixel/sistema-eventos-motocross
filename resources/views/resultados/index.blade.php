<x-app-layout>
    @if(!auth()->check() || !in_array(auth()->user()->role, ['organizador', 'administrador'], true))
        @if(auth()->check() && auth()->user()->role === 'piloto')
            <header class="pilot-results-topbar">
                <a href="{{ route('dashboard') }}" aria-label="Volver al panel">‹</a>
                <h1>Resultados</h1>
                <a class="pilot-results-avatar" href="{{ route('profile.edit') }}" aria-label="Mi perfil">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</a>
            </header>
        @endif
        <div class="workspace-page pilot-public-results-page {{ auth()->check() && auth()->user()->role === 'piloto' ? 'pilot-results-screen' : '' }}" x-data="{ selectedEvent: '{{ $events->first()?->id ?? 'all' }}' }">
            @unless(auth()->check() && auth()->user()->role === 'piloto')
                <header class="workspace-page-heading">
                    <div><span class="workspace-eyebrow">Competición</span><h1>Resultados</h1><p>Posiciones y puntos de las competencias publicadas.</p></div>
                </header>
            @endunless

            @if($events->isNotEmpty())
                @if(auth()->check() && auth()->user()->role === 'piloto')
                    <label class="pilot-public-results-select-label" for="pilot-results-event">Evento</label>
                @endif
                <select id="pilot-results-event" class="form-select pilot-public-results-select" aria-label="Seleccionar evento" x-model="selectedEvent">
                    @foreach($events as $event)<option value="{{ $event->id }}">{{ $event->title }} · {{ $event->event_date?->format('d/m/Y') }}</option>@endforeach
                </select>

                @foreach($events as $event)
                    @php
                        $publicResults = $event->registrations
                            ->filter(fn ($registration) => $registration->result_position !== null || $registration->result_points !== null)
                            ->sortBy(fn ($registration) => $registration->result_position ?? PHP_INT_MAX);
                        $categoryResults = $publicResults->groupBy(fn ($registration) => $registration->category?->name ?: 'General');
                        $categoryKeys = $categoryResults->keys()->mapWithKeys(fn ($name) => [$name => \Illuminate\Support\Str::slug($name)]);
                        $firstCategoryKey = $categoryKeys->first() ?? 'all';
                    @endphp
                    <section class="pilot-public-results-event" x-data="{ displayMode: 'general', category: '{{ $firstCategoryKey }}' }" x-show="selectedEvent === '{{ $event->id }}'" x-cloak>
                        @if($categoryResults->isNotEmpty())
                            <div class="pilot-results-mode-tabs" role="tablist" aria-label="Modo de resultados">
                                <button type="button" role="tab" :aria-selected="displayMode === 'general'" :class="{ 'is-active': displayMode === 'general' }" @click="displayMode = 'general'">General</button>
                                <button type="button" role="tab" :aria-selected="displayMode === 'category'" :class="{ 'is-active': displayMode === 'category' }" @click="displayMode = 'category'">Por categoría</button>
                            </div>
                            <nav class="pilot-results-category-tabs" aria-label="Categorías" x-show="displayMode === 'category'" x-cloak>
                                @foreach($categoryResults as $categoryName => $registrations)
                                    @php
                                        $categoryKey = $categoryKeys[$categoryName];
                                    @endphp
                                    <button type="button" :class="{ 'is-active': category === '{{ $categoryKey }}' }" @click="category = '{{ $categoryKey }}'">{{ $categoryName }}</button>
                                @endforeach
                            </nav>
                            @foreach($categoryResults as $categoryName => $registrations)
                                @php
                                    $categoryKey = $categoryKeys[$categoryName];
                                @endphp
                                <section class="pilot-results-category" x-show="displayMode === 'general' || category === '{{ $categoryKey }}'">
                                    <h2>{{ $categoryName }}</h2>
                                    <div class="table-responsive pilot-public-results-table-wrap">
                                        <table class="table align-middle mb-0 pilot-public-results-table">
                                            <thead><tr><th>Pos.</th><th>Piloto</th><th>N.º</th><th class="text-end">Puntos</th></tr></thead>
                                            <tbody>
                                            @foreach($registrations as $registration)
                                                @php
                                                    $isCurrentPilot = auth()->check() && auth()->user()->role === 'piloto'
                                                        && ($registration->user_id === auth()->id() || $registration->participant?->email === auth()->user()->email);
                                                @endphp
                                                <tr @class(['is-current-pilot' => $isCurrentPilot])>
                                                    <td>{{ $registration->result_position ?: '—' }}</td>
                                                    <td><strong>{{ $registration->participant?->name ?? 'Piloto' }}</strong></td>
                                                    <td>{{ $registration->dorsal ?: '—' }}</td>
                                                    <td class="text-end"><strong>{{ $registration->result_points ?? '—' }}</strong></td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </section>
                            @endforeach
                        @else
                            <div class="pilot-public-results-empty">No hay posiciones publicadas para este evento todavía.</div>
                        @endif
                        @if($event->championship && $event->round_number === $event->championship->total_rounds)
                            @include('resultados.partials.championship-standings', ['event' => $event, 'championshipStandings' => $championshipStandings])
                        @endif
                    </section>
                @endforeach
            @else
                <div class="pilot-public-results-empty"><strong>Aún no hay resultados publicados</strong><p>Cuando termine una competencia y se publiquen sus resultados, aparecerán aquí.</p><a href="{{ route('eventos.index') }}">Ver eventos</a></div>
            @endif
        </div>
    @else
    <div class="container-fluid py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-success mb-2">Competición</p>
                <h2 class="fw-bold mb-1">Gestión de resultados</h2>
                <p class="text-muted mb-0">Actualiza posiciones y puntos para cada participante. Los cambios se publican de inmediato.</p>
            </div>
            <a href="{{ route('organizador.mis-eventos') }}" class="btn btn-outline-secondary mt-3 mt-md-0">Volver a mis eventos</a>
        </div>

        @if(session('success')) 
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div> 
        @endif

        @forelse($events as $event)
            <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                <div class="card-header bg-white border-bottom py-4 px-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <span class="d-block text-uppercase small fw-bold text-secondary mb-1">Evento</span>
                            <h3 class="fw-bold text-dark mb-2">{{ $event->title }}</h3>
                            <div class="small text-dark">
                                <div class="mb-1">📅 {{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }} · 📍 {{ $event->location }}</div>
                                <div class="mb-1">📝 {{ $event->description ? Str::limit($event->description, 80) : 'Sin descripción' }}</div>
                                @if($event->championship)
                                    <div class="mb-1">🏆 {{ $event->championship->name }} · Fecha {{ $event->round_number }} de {{ $event->championship->total_rounds }}</div>
                                    <div class="mb-1 text-primary fw-semibold">Puntos automáticos por puesto: 1.º 25 · 2.º 22 · 3.º 20 · 4.º 18 · 5.º 16 · hasta 1 punto al puesto 20.</div>
                                @endif
                                @if($event->categories->count())
                                    <div>🏷️ Categorías: {{ $event->categories->pluck('name')->implode(', ') }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="mb-2">
                                @if($event->status === 'finalizado' && !$event->results_published)
                                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">RESULTADOS PENDIENTES</span>
                                @elseif($event->results_published)
                                    <span class="badge bg-success fs-6 px-3 py-2">RESULTADOS PUBLICADOS</span>
                                @else
                                    <span class="badge bg-info fs-6 px-3 py-2">EN EDICIÓN</span>
                                @endif
                            </div>
                            <div class="small text-secondary">
                                Estado: <strong class="text-dark">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                @if($event->status === 'finalizado' && !$event->results_published)
                    <div class="alert alert-warning border-0 rounded-0 mb-0 p-3 d-flex align-items-center gap-2">
                        <strong>RESULTADOS PENDIENTES</strong>
                        <div>El evento ha finalizado, pero todavía no has publicado los resultados.</div>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%">Participante</th>
                                <th style="width: 15%">Categoría</th>
                                <th style="width: 10%" class="text-center">Dorsal</th>
                                <th style="width: 12%" class="text-center">Posición</th>
                                <th style="width: 12%" class="text-center">Puntos</th>
                                <th style="width: 26%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($event->registrations->where('status', 'confirmada')->sortBy(fn($r) => $r->result_position ?? PHP_INT_MAX) as $registration)
                                @php
                                    $categoryRegistrationCount = $event->registrations
                                        ->where('status', 'confirmada')
                                        ->where('category_id', $registration->category_id)
                                        ->count();
                                    $championshipScoringRules = $event->championship?->scoringRules ?? collect();
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $registration->participant->name ?? 'Participante' }}</div>
                                        <small class="text-muted">{{ $registration->participant->license ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary">{{ $registration->category->name ?? 'Sin categoría' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark">{{ $registration->dorsal ?: '-' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <select id="result-position-{{ $registration->id }}" form="result-form-{{ $registration->id }}" name="result_position" class="form-select form-select-sm text-center" data-points-input="{{ $event->championship_id ? 'true' : 'false' }}" data-points-target="result-points-{{ $registration->id }}" style="max-width: 90px; margin: auto;">
                                            <option value="">-</option>
                                            @for($position = 1; $position <= $categoryRegistrationCount; $position++)
                                                @php
                                                    $configuredPoints = $championshipScoringRules
                                                        ->first(fn ($rule) => (int) $rule->category_id === (int) $registration->category_id
                                                            && (int) $rule->round_number === (int) $event->round_number
                                                            && (int) $rule->position === $position)?->points;
                                                    $defaultPoints = [1 => 25, 2 => 22, 3 => 20, 4 => 18, 5 => 16][$position] ?? max(0, 21 - $position);
                                                @endphp
                                                <option value="{{ $position }}" data-points="{{ $configuredPoints ?? $defaultPoints }}" @selected((string) $registration->result_position === (string) $position)>{{ $position }}.º</option>
                                            @endfor
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <input id="result-points-{{ $registration->id }}" form="result-form-{{ $registration->id }}" name="result_points" type="number" min="0" class="form-control form-control-sm text-center" placeholder="-" value="{{ $registration->result_points }}" @readonly($event->championship_id !== null) style="max-width: 80px; margin: auto;">
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <form id="result-form-{{ $registration->id }}" method="POST" action="{{ route('resultados.update', $registration) }}" style="display: inline;">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-success">💾 Guardar</button>
                                            </form>
                                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $registration->id }}" title="Editar resultado">✏️ Editar</button>
                                            <form method="POST" action="{{ route('resultados.delete', $registration) }}" style="display: inline;" onsubmit="return confirm('¿Eliminar este resultado?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="Eliminar resultado">🗑️ Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal de edición para {{ $registration->participant->name }} -->
                                <div class="modal fade" id="editModal{{ $registration->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Editar resultado - {{ $registration->participant->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="{{ route('resultados.update', $registration) }}" class="resultForm" data-modal-id="editModal{{ $registration->id }}">
                                                @csrf @method('PATCH')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Posición</label>
                                                        <select name="result_position" class="form-select" data-points-input="{{ $event->championship_id ? 'true' : 'false' }}" data-points-target="modal-result-points-{{ $registration->id }}">
                                                            <option value="">Sin posición</option>
                                                            @for($position = 1; $position <= $categoryRegistrationCount; $position++)
                                                                @php
                                                                    $configuredPoints = $championshipScoringRules
                                                                        ->first(fn ($rule) => (int) $rule->category_id === (int) $registration->category_id
                                                                            && (int) $rule->round_number === (int) $event->round_number
                                                                            && (int) $rule->position === $position)?->points;
                                                                    $defaultPoints = [1 => 25, 2 => 22, 3 => 20, 4 => 18, 5 => 16][$position] ?? max(0, 21 - $position);
                                                                @endphp
                                                                <option value="{{ $position }}" data-points="{{ $configuredPoints ?? $defaultPoints }}" @selected((string) $registration->result_position === (string) $position)>{{ $position }}.º lugar</option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Puntos</label>
                                                        <input id="modal-result-points-{{ $registration->id }}" type="number" name="result_points" class="form-control" min="0" value="{{ $registration->result_points }}" placeholder="Ej: 25, 22, 20..." @readonly($event->championship_id !== null)>
                                                    </div>
                                                    @if($event->championship_id)
                                                        <div class="form-text mb-3">Los puntos se asignan automáticamente según el puesto.</div>
                                                    @endif
                                                    <div class="alert alert-info small mb-0">
                                                        <strong>Piloto:</strong> {{ $registration->participant->name }}<br>
                                                        <strong>Categoría:</strong> {{ $registration->category->name ?? 'Sin categoría' }}<br>
                                                        <strong>Dorsal:</strong> {{ $registration->dorsal ?: '-' }}
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary">💾 Guardar cambios</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No hay participantes confirmados para este evento.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($event->championship && $event->round_number === $event->championship->total_rounds)
                    @include('resultados.partials.championship-standings', ['event' => $event, 'championshipStandings' => $championshipStandings])
                @endif

                <div class="card-footer bg-light py-3 px-4">
                    <small class="text-muted">
                        Total confirmados: <strong>{{ $event->registrations->where('status', 'confirmada')->count() }}</strong> · 
                        Con resultados: <strong>{{ $event->registrations->where('status', 'confirmada')->whereNotNull('result_position')->count() }}</strong>
                    </small>
                </div>
            </div>
        @empty
            <div class="alert alert-light border text-center py-5">
                <p class="mb-0 text-muted">
                    No hay eventos en curso o finalizados para actualizar resultados.
                </p>
                <a href="{{ route('eventos.create') }}" class="btn btn-sm btn-primary mt-3">Crear un nuevo evento</a>
            </div>
        @endforelse
    </div>

    <script>
        const championshipPointsForPosition = position => {
            const topFive = [25, 22, 20, 18, 16];
            return position <= topFive.length ? topFive[position - 1] : Math.max(0, 21 - position);
        };

        document.addEventListener('change', event => {
            const positionInput = event.target.closest('[data-points-input="true"]');
            if (!positionInput) {
                return;
            }

            const pointsInput = document.getElementById(positionInput.dataset.pointsTarget);
            if (pointsInput) {
                const configuredPoints = positionInput.selectedOptions[0]?.dataset.points;
                pointsInput.value = positionInput.value
                    ? (configuredPoints ?? championshipPointsForPosition(Number(positionInput.value)))
                    : '';
            }
        });

        document.querySelectorAll('.resultForm').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(form);
                const modalId = form.getAttribute('data-modal-id');
                
                try {
                    const response = await fetch(form.action, {
                        method: form.method,
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    if (response.ok) {
                        // Cerrar el modal
                        const modal = document.getElementById(modalId);
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) bsModal.hide();
                        
                        // Mostrar notificación de éxito
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });
        });
    </script>
    @endif
</x-app-layout>