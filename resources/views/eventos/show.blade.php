<x-app-layout>
    <div class="workspace-page">
        <div class="workspace-page-heading">
            <div><span class="workspace-eyebrow">Evento</span><h1>Detalles del evento</h1></div>
            <a href="{{ route('eventos.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <section class="workspace-event-overview">
                <div class="workspace-event-photo">
                    @if($event->cover_path)
                        <img src="{{ url('/storage/'.$event->cover_path) }}" alt="Portada de {{ $event->title }}">
                    @else
                        <div class="workspace-event-photo-empty"><span>AMC</span><small>Portada del evento</small></div>
                    @endif
                </div>
                <div class="workspace-event-info">
                    <div class="workspace-event-info-top">
                        <span class="badge {{ $event->status === 'programado' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                        @if(auth()->check() && in_array(auth()->user()->role, ['organizador', 'administrador']))
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('eventos.edit', $event) }}">Editar</a>
                        @endif
                    </div>
                    <h2>{{ $event->title }}</h2>
                    <dl class="workspace-event-facts">
                        <div><dt>Fecha</dt><dd>▣ {{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</dd></div>
                        <div><dt>Hora</dt><dd>◷ {{ $event->start_time ? substr($event->start_time, 0, 5) : 'Por confirmar' }}</dd></div>
                        <div><dt>Ubicación</dt><dd>⌖ {{ $event->location ?: 'No especificada' }}</dd></div>
                    </dl>
                    @if($event->latitude !== null && $event->longitude !== null)
                        <button type="button" class="workspace-map-link" data-bs-toggle="modal" data-bs-target="#event-map-viewer-modal">Ver en el mapa ↗</button>
                    @endif
                </div>
            </section>

            <div class="card-body">
                @php
                    $categoryFees = $event->categories
                        ->map(fn ($category) => $category->pivot->entry_fee ?? $event->entry_fee)
                        ->filter(fn ($fee) => $fee !== null)
                        ->map(fn ($fee) => (float) $fee)
                        ->unique()
                        ->values();
                    if ($event->categories->isEmpty() && $event->entry_fee !== null) {
                        $categoryFees->push((float) $event->entry_fee);
                    }
                    $feeSummary = $categoryFees->isEmpty()
                        ? 'No definido'
                        : ($categoryFees->count() > 1 ? 'Varía por categoría' : 'Bs. '.number_format($categoryFees->first(), 2));
                @endphp
                <div class="workspace-event-stats">
                    <div><span class="workspace-stat-icon">♧</span><small>Categorías</small><strong>{{ $event->categories->count() }}</strong></div>
                    <div><span class="workspace-stat-icon">♙</span><small>Inscripciones</small><strong>{{ $event->active_registrations_count }} pilotos</strong></div>
                    <div><span class="workspace-stat-icon">Bs</span><small>Costo de inscripción</small><strong>{{ $feeSummary }}</strong></div>
                </div>

                <section class="workspace-quick-actions">
                    <h3>Acciones rápidas</h3>
                    <div>
                        @if(auth()->check() && in_array(auth()->user()->role, ['organizador', 'administrador']))
                            <a class="btn btn-primary" href="{{ route('inscripciones.index', ['event_id' => $event->id]) }}">Ver pilotos inscritos</a>
                            <a class="btn btn-outline-primary" href="{{ route('eventos.edit', $event) }}">Editar evento</a>
                            @if($event->status === 'finalizado' && $event->results_published)
                                <a class="btn btn-outline-primary" href="{{ route('eventos.resultados', $event) }}">Ver resultados</a>
                            @endif
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#starting-order-modal">Orden de largada</button>
                        @endif
                        @if($event->status === 'programado')
                            <a href="{{ route('inscripciones.create', ['event_id' => $event->id]) }}" class="btn btn-primary">Inscribirme</a>
                        @endif
                        @if($event->organizer_whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/\D+/', '', $event->organizer_whatsapp) }}?text={{ urlencode('Hola, tengo una consulta sobre el evento '.$event->title) }}" class="btn btn-success" target="_blank" rel="noopener noreferrer" aria-label="Contactar por WhatsApp al organizador de {{ $event->title }}">
                                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.5 11.8a8.3 8.3 0 0 1-12.2 7.3l-4.4 1.2 1.2-4.3a8.3 8.3 0 1 1 15.4-4.2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 8.2c.2-.5.5-.5.8-.5h.5c.2 0 .4.1.5.4l.7 1.7c.1.2.1.4-.1.6l-.5.7c-.2.2-.2.4 0 .6.5.9 1.1 1.5 2.1 2 .2.1.4.1.6-.1l.6-.7c.2-.2.4-.2.6-.1l1.7.8c.3.1.4.3.4.5 0 .5-.2 1.1-.6 1.4-.4.4-1 .6-1.6.5-1.1-.2-2.4-.8-3.7-2-1.2-1.1-2-2.4-2.3-3.5-.2-.7.1-1.5.5-2.1Z" fill="currentColor"/></svg>
                                WhatsApp del organizador
                            </a>
                        @endif
                    </div>
                </section>

                @if(auth()->check() && in_array(auth()->user()->role, ['organizador', 'administrador']) && $event->status === 'finalizado')
                    @php
                        $inscritos = $event->registrations()->count();
                        $aceptados = $event->registrations()->where('status', 'confirmada')->count();
                        $participaron = $event->registrations()->where('status', 'confirmada')->where('participated', true)->count();
                        $noParticiparon = $aceptados - $participaron;
                    @endphp

                    <div class="alert alert-warning border-0 shadow-sm mb-4">
                        <strong>RESULTADOS PENDIENTES</strong>
                        <div class="mt-2 mb-0">El evento ha finalizado, pero todavía no has publicado los resultados.</div>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold mb-3">Resumen del evento</h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="bg-light rounded p-3 h-100">
                                    <small class="text-muted d-block">Inscritos</small>
                                    <strong class="fs-4">{{ $inscritos }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-light rounded p-3 h-100">
                                    <small class="text-muted d-block">Aceptados</small>
                                    <strong class="fs-4">{{ $aceptados }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-light rounded p-3 h-100">
                                    <small class="text-muted d-block">Participaron</small>
                                    <strong class="fs-4">{{ $participaron }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-light rounded p-3 h-100">
                                    <small class="text-muted d-block">No participaron</small>
                                    <strong class="fs-4">{{ $noParticiparon }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="workspace-detail-columns">
                    <section class="workspace-detail-panel">
                        <h3>Categorías y costo de inscripción</h3>
                        <div class="workspace-detail-tags workspace-category-fees">
                            @forelse($event->categories as $category)
                                @php($categoryFee = $category->pivot->entry_fee ?? $event->entry_fee)
                                <span class="workspace-category-fee"><strong>{{ $category->name }}</strong><small>{{ $categoryFee !== null ? 'Bs. '.number_format((float) $categoryFee, 2) : 'Costo no definido' }}</small></span>
                            @empty
                                <span class="text-muted">No especificadas</span>
                            @endforelse
                        </div>
                        <div class="workspace-detail-facts">
                            <div><small>Cierre de inscripciones</small><strong>{{ $event->registration_deadline ? \Carbon\Carbon::parse($event->registration_deadline)->format('d/m/Y') : 'No especificado' }}</strong></div>
                            <div><small>Cupo máximo</small><strong>{{ $event->max_participants ?? 'Sin límite' }}</strong></div>
                        </div>
                    </section>

                    @if($event->prizes->isNotEmpty())
                        <section class="workspace-detail-panel">
                            <h3>
                                @if($event->championship && $event->round_number === $event->championship->total_rounds)
                                    Premios de la clasificación general
                                @elseif($event->championship)
                                    Trofeos de la fecha
                                @else
                                    Premios por categoría
                                @endif
                            </h3>
                            @if($event->championship && $event->round_number === $event->championship->total_rounds)
                                <p class="small text-muted">El premio mayor se entrega al piloto con más puntos acumulados en el campeonato.</p>
                            @endif
                            <div class="workspace-prize-grid">
                                @foreach($event->prizes->groupBy('category_id') as $categoryPrizes)
                                    <div class="workspace-prize-card">
                                        <strong>{{ $categoryPrizes->first()->category->name }}</strong>
                                        @foreach($categoryPrizes as $prize)
                                            <div class="workspace-prize-row"><span>{{ $prize->position }}.° lugar · {{ $prize->award_type }}</span>@if($prize->amount !== null)<b>Bs. {{ number_format((float) $prize->amount, 2) }}</b>@endif</div>
                                            @if($prize->description)<small class="text-muted">{{ $prize->description }}</small>@endif
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                @if($event->startingOrders->isNotEmpty())
                    <section class="mt-5">
                        <h5 class="fw-bold mb-3">🏁 Orden de largada</h5>
                        <div class="d-grid gap-3">
                            @foreach($event->startingOrders as $startingOrder)
                                <div class="border rounded p-3">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <h6 class="fw-bold mb-0">{{ $startingOrder->position }}. {{ $startingOrder->category->name }}</h6>
                                        @if($startingOrder->scheduled_time)
                                            <span class="text-muted">{{ substr($startingOrder->scheduled_time, 0, 5) }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-3">
                                        <small class="text-muted d-block mb-2">Pilotos inscritos</small>
                                        @forelse($startingOrder->registrations as $registration)
                                            <span class="badge bg-light text-dark border me-1 mb-1">#{{ $registration->dorsal ?: '-' }} - {{ $registration->participant->name }}</span>
                                        @empty
                                            <span class="small text-muted">No hay pilotos inscritos en esta categoría.</span>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($event->latitude !== null && $event->longitude !== null)
                    @include('eventos.partials.map-viewer', ['showInlineMap' => false])
                @endif

                @if($event->description)
                    <section class="workspace-detail-panel mt-3">
                        <h3>Descripción del evento</h3>
                        <p class="mb-0 text-muted">{{ $event->description }}</p>
                    </section>
                @endif
            </div>
        </div>
    </div>

    @if(auth()->check() && in_array(auth()->user()->role, ['organizador', 'administrador']))
        <div class="modal fade modal-starting-order" id="starting-order-modal" tabindex="-1" aria-labelledby="starting-order-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('eventos.update', $event) }}">
                        @csrf
                        @method('PATCH')

                        <input type="hidden" name="title" value="{{ $event->title }}">
                        <input type="hidden" name="event_date" value="{{ $event->event_date }}">
                        @if($event->start_time)
                            <input type="hidden" name="start_time" value="{{ substr($event->start_time, 0, 5) }}">
                        @endif
                        <input type="hidden" name="location" value="{{ $event->location }}">
                        <input type="hidden" name="status" value="{{ $event->status }}">
                        <input type="hidden" name="description" value="{{ $event->description }}">
                        <input type="hidden" name="entry_fee" value="{{ $event->entry_fee }}">
                        <input type="hidden" name="payment_instructions" value="{{ $event->payment_instructions }}">
                        <input type="hidden" name="registration_deadline" value="{{ $event->registration_deadline }}">
                        <input type="hidden" name="max_participants" value="{{ $event->max_participants }}">
                        <input type="hidden" name="latitude" value="{{ $event->latitude }}">
                        <input type="hidden" name="longitude" value="{{ $event->longitude }}">
                        @foreach($event->categories as $category)
                            <input type="hidden" name="category_ids[]" value="{{ $category->id }}">
                        @endforeach

                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="starting-order-modal-label">🏁 Orden de largada</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>

                        <div class="modal-body">
                            <p class="small text-muted mb-3">Define la salida de cada categoría y publica el orden cuando quieras.</p>

                            <div id="starting-order-modal-container" class="d-grid gap-3"></div>
                            <div id="starting-order-modal-empty" class="small text-muted mt-2 {{ $event->startingOrders->isNotEmpty() ? 'd-none' : '' }}">Todavía no hay categorías en el orden de largada.</div>

                            <div class="d-flex justify-content-end mt-3">
                                <button type="button" class="btn btn-outline-dark btn-sm" id="add-starting-order-modal-row">+ Agregar categoría</button>
                            </div>

                            <div class="form-check mt-3 p-2 rounded-3 bg-light border">
                                <input type="hidden" name="publish_starting_order" value="0">
                                <input type="checkbox" name="publish_starting_order" value="1" class="form-check-input" id="publish-starting-order-modal" @checked($event->startingOrders->contains(fn ($item) => $item->is_published))>
                                <label class="form-check-label fw-semibold" for="publish-starting-order-modal">Publicar ahora para el público</label>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-dark">Guardar orden</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

        <style>
            .event-cover {
                position: relative;
                isolation: isolate;
                min-height: 300px;
                background-size: cover;
                background-position: center 42%;
                display: flex;
                align-items: flex-start;
                padding: clamp(2rem, 6vw, 4.5rem) clamp(1.5rem, 6vw, 5rem);
                overflow: hidden;
            }
            .event-cover::before {
                content: '';
                position: absolute;
                inset: 1rem;
                border: 1px solid rgba(255, 255, 255, 0.35);
                pointer-events: none;
            }
            .event-cover::after {
                content: '';
                position: absolute;
                top: -35%;
                right: 10%;
                width: 18%;
                height: 170%;
                background: rgba(239, 68, 68, 0.72);
                transform: rotate(18deg);
                mix-blend-mode: screen;
                opacity: 0.6;
                pointer-events: none;
            }
            .event-cover-inner {
                position: relative;
                z-index: 1;
                width: 100%;
                color: #fff;
                max-width: 1200px;
                margin: 0 auto;
            }
            .event-cover-title {
                max-width: 850px;
                font-size: 4.5rem;
                font-weight: 900;
                line-height: 0.9;
                letter-spacing: 0;
                text-transform: uppercase;
                text-shadow: 0 5px 0 rgba(0, 0, 0, 0.28), 0 12px 28px rgba(0, 0, 0, 0.45);
                margin: 0 0 1rem;
            }
            .event-cover-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                font-size: 0.9rem;
                font-weight: 700;
                text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
            }
            .event-cover-meta span {
                display: grid;
                gap: 0.15rem;
                padding: 0.55rem 0.7rem;
                background: rgba(5, 10, 16, 0.72);
                border: 1px solid rgba(255, 255, 255, 0.24);
                border-radius: 3px;
            }
            .event-cover-meta small {
                color: #facc15;
                font-size: 0.65rem;
                font-weight: 800;
                text-transform: uppercase;
            }
            .event-cover-location {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                width: fit-content;
                max-width: 100%;
                margin-top: 0.75rem;
                padding: 0.7rem 0.85rem;
                background: rgba(5, 10, 16, 0.86);
                border: 1px solid rgba(255, 255, 255, 0.32);
                border-left: 4px solid #dc2626;
                box-shadow: 0 8px 22px rgba(0, 0, 0, 0.3);
            }
            .event-cover-location-icon {
                flex: 0 0 auto;
                font-size: 1.4rem;
                line-height: 1;
            }
            .event-cover-location-copy {
                display: grid;
                gap: 0.1rem;
                min-width: 0;
            }
            .event-cover-location-copy small {
                color: #facc15;
                font-size: 0.65rem;
                font-weight: 800;
                text-transform: uppercase;
            }
            .event-cover-location-copy strong {
                overflow-wrap: anywhere;
            }
            .event-cover-location-link,
            .event-cover-location-pending {
                flex: 0 0 auto;
                margin-left: 0.5rem;
                color: #fff;
                font-size: 0.78rem;
                font-weight: 800;
                padding: 0;
                border: 0;
                background: transparent;
                cursor: pointer;
                text-decoration: underline;
                text-underline-offset: 3px;
            }
            .event-cover-location-link:hover {
                color: #facc15;
            }
            .event-cover-location-pending {
                color: rgba(255, 255, 255, 0.7);
                text-decoration: none;
            }
            .event-cover-section {
                margin-top: 1rem;
            }
            .event-cover-section-title {
                margin: 0 0 0.45rem;
                color: #facc15;
                font-size: 0.85rem;
                font-weight: 900;
                text-transform: uppercase;
                text-shadow: 0 2px 8px rgba(0, 0, 0, 0.8);
            }
            .event-cover-categories {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .event-cover-categories span {
                display: inline-flex;
                align-items: center;
                padding: 0.45rem 0.8rem;
                border-radius: 3px;
                background: #dc2626;
                border: 1px solid rgba(255,255,255,0.22);
                font-size: 0.8rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }
            .event-cover-schedule {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .event-cover-schedule span {
                display: flex;
                flex-wrap: wrap;
                gap: 0.45rem;
                padding: 0.45rem 0.65rem;
                background: rgba(5, 10, 16, 0.76);
                border: 1px solid rgba(255, 255, 255, 0.25);
                border-radius: 3px;
                font-size: 0.8rem;
            }
            .event-cover-prizes {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(min(100%, 230px), 1fr));
                gap: 0.5rem;
            }
            .event-cover-prize-category {
                min-width: 0;
                padding: 0.65rem;
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 3px;
                background: rgba(5, 10, 16, 0.72);
            }
            .event-cover-prize-category h3 {
                margin: 0 0 0.35rem;
                color: #fff;
                font-size: 0.85rem;
                font-weight: 800;
                text-transform: uppercase;
            }
            .event-cover-award {
                display: flex;
                flex-wrap: wrap;
                align-items: baseline;
                gap: 0.2rem 0.5rem;
                padding: 0.4rem 0;
                border-top: 1px solid rgba(255, 255, 255, 0.18);
                font-size: 0.78rem;
            }
            .event-cover-award > strong {
                color: #facc15;
                white-space: nowrap;
            }
            .event-cover-award > span {
                color: rgba(255, 255, 255, 0.88);
            }
            .event-cover-award-amount {
                margin-left: auto;
                color: #fff !important;
                font-weight: 800;
                white-space: nowrap;
            }
            .event-cover-award small {
                flex-basis: 100%;
                color: rgba(255, 255, 255, 0.72);
                overflow-wrap: anywhere;
            }
            .event-cover-description {
                max-width: 850px;
                margin: 0;
                padding: 0.65rem;
                background: rgba(5, 10, 16, 0.72);
                border-left: 3px solid #dc2626;
                font-size: 0.9rem;
                line-height: 1.5;
                white-space: pre-line;
                overflow-wrap: anywhere;
            }
            @media (max-width: 575.98px) {
                .event-cover {
                    padding: 2rem 1.5rem;
                }
                .event-cover-title {
                    font-size: 2.5rem;
                    overflow-wrap: anywhere;
                }
                .event-cover-meta span {
                    flex: 1 1 135px;
                }
                .event-cover-location {
                    align-items: flex-start;
                    flex-wrap: wrap;
                }
                .event-cover-location-link,
                .event-cover-location-pending {
                    margin-left: calc(1.4rem + 0.75rem);
                }
                .event-cover::before {
                    inset: 0.65rem;
                }
                .event-cover::after {
                    right: -8%;
                    width: 28%;
                }
            }
            .modal-starting-order .modal-content {
                border: 0;
                border-radius: 22px;
                box-shadow: 0 18px 45px rgba(15, 23, 42, 0.22);
                overflow: hidden;
            }
            .modal-starting-order .modal-header {
                background: linear-gradient(135deg, #0b1220, #1e293b 55%, #111827);
                color: #fff;
                padding: 1rem 1.25rem;
            }
            .modal-starting-order .modal-header .btn-close {
                filter: invert(1) grayscale(100%) brightness(200%);
            }
            .modal-starting-order .modal-body {
                padding: 1.25rem;
                background: linear-gradient(180deg, #f8fafc, #eef2f7);
            }
            .modal-starting-order .starting-order-modal-row {
                background: #fff;
                border: 1px solid #e2e8f0 !important;
                border-radius: 14px;
                padding: 0.9rem !important;
                box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
            }
            .modal-starting-order .form-label {
                font-size: 0.72rem;
                font-weight: 800;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                color: #475569;
            }
            .modal-starting-order .btn-outline-dark,
            .modal-starting-order .btn-dark,
            .modal-starting-order .btn-outline-secondary,
            .modal-starting-order .btn-outline-danger {
                border-radius: 12px;
                font-weight: 600;
            }
            .modal-starting-order .btn-dark {
                background: #111827;
                border-color: #111827;
            }
            .modal-starting-order .btn-dark:hover {
                background: #0b1220;
            }
            .modal-starting-order .btn-outline-danger {
                border-color: #ef4444;
                color: #b91c1c;
            }
        </style>

    @if(auth()->check() && in_array(auth()->user()->role, ['organizador', 'administrador']))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalContainer = document.getElementById('starting-order-modal-container');
                const emptyState = document.getElementById('starting-order-modal-empty');
                const categories = @json($event->categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])->values());

                const categoryOptions = function () {
                    return categories.map(category => `
                        <option value="${category.id}">${category.name}</option>
                    `).join('');
                };

                const renumberRows = function () {
                    modalContainer.querySelectorAll('.starting-order-modal-row').forEach((row, index) => {
                        const position = row.querySelector('.starting-order-position');
                        if (position) {
                            position.value = index + 1;
                        }
                    });
                };

                const addRow = function ({ categoryId = '', scheduledTime = '', position = '' } = {}) {
                    const index = Date.now() + Math.floor(Math.random() * 1000);
                    modalContainer.insertAdjacentHTML('beforeend', `
                        <div class="row g-2 align-items-end border rounded p-3 starting-order-modal-row">
                            <div class="col-md-5">
                                <label class="form-label small mb-1">Categoría</label>
                                <select name="starting_orders[${index}][category_id]" class="form-select" required>
                                    <option value="">Selecciona</option>
                                    ${categoryOptions()}
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Orden</label>
                                <input type="number" name="starting_orders[${index}][position]" class="form-control starting-order-position" min="1" max="100" value="${position}" readonly required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Hora</label>
                                <input type="time" name="starting_orders[${index}][scheduled_time]" class="form-control" value="${scheduledTime}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger w-100 remove-starting-order-modal-row">Quitar</button>
                            </div>
                        </div>
                    `);

                    const select = modalContainer.lastElementChild.querySelector('select');
                    if (categoryId) {
                        select.value = String(categoryId);
                    }

                    renumberRows();
                    emptyState.classList.toggle('d-none', modalContainer.children.length > 0);
                };

                @foreach($event->startingOrders as $startingOrder)
                    addRow({
                        categoryId: '{{ $startingOrder->category_id }}',
                        scheduledTime: '{{ $startingOrder->scheduled_time ? substr($startingOrder->scheduled_time, 0, 5) : '' }}',
                        position: '{{ $startingOrder->position }}'
                    });
                @endforeach

                document.getElementById('add-starting-order-modal-row')?.addEventListener('click', function () {
                    addRow();
                });

                document.addEventListener('click', function (event) {
                    if (!event.target.classList.contains('remove-starting-order-modal-row')) return;
                    const row = event.target.closest('.starting-order-modal-row');
                    if (row) {
                        row.remove();
                        renumberRows();
                        emptyState.classList.toggle('d-none', modalContainer.children.length > 0);
                    }
                });
            });
        </script>
    @endif
</x-app-layout>
