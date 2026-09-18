<x-app-layout>
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-primary mb-2">Evento</p>
                <h2 class="fw-bold mb-1">Detalles del evento</h2>
            </div>
            <a href="{{ route('eventos.index') }}" class="btn btn-outline-primary">Volver</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <span class="badge bg-primary-subtle text-primary mb-2">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                        <h3 class="fw-bold mb-1">{{ $event->title }}</h3>
                    </div>
                    @if($event->status === 'programado')
                        <a href="{{ route('inscripciones.create', ['event_id' => $event->id]) }}" class="btn btn-primary">Inscribirme</a>
                    @endif
                </div>

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

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded">
                            <p class="text-muted mb-1">Fecha</p>
                            <h5 class="mb-0">{{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</h5>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded">
                            <p class="text-muted mb-1">Ubicación</p>
                            <h5 class="mb-0 text-break">{{ $event->location }}</h5>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded h-100">
                            <p class="text-muted mb-1">Costo de inscripción</p>
                            <h5 class="mb-0">{{ $event->entry_fee !== null ? '$'.number_format($event->entry_fee, 2) : 'No especificado' }}</h5>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded h-100">
                            <p class="text-muted mb-1">Cupo máximo</p>
                            <h5 class="mb-0">{{ $event->max_participants ?? 'Sin límite especificado' }}</h5>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded h-100">
                            <p class="text-muted mb-1">Fecha límite de inscripción</p>
                            <h5 class="mb-0">{{ $event->registration_deadline ? \Carbon\Carbon::parse($event->registration_deadline)->format('d/m/Y') : 'No especificada' }}</h5>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded h-100">
                            <p class="text-muted mb-1">Categorías</p>
                            <h5 class="mb-0 text-break">{{ $event->categories->pluck('name')->join(', ') ?: 'No especificadas' }}</h5>
                        </div>
                    </div>
                </div>

                @if($event->latitude !== null && $event->longitude !== null)
                    <div class="mt-4">
                        <h5 class="fw-bold mb-2">Ubicación del circuito</h5>
                        @include('eventos.partials.map-viewer')
                    </div>
                @endif

                <div class="mt-4">
                    <h5 class="fw-bold mb-2">Descripción</h5>
                    <p class="mb-0 text-muted">
                        {{ $event->description ?: 'Este evento no tiene descripción adicional.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
