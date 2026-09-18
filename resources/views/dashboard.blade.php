<x-app-layout>
    <div class="container-fluid py-4">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-danger mb-2">Administración</p>
                <h2 class="fw-bold mb-1">Panel de control</h2>
                <p class="mb-1">
                    Bienvenido,
                    <strong>{{ Auth::user()->name }}</strong>
                    <span class="badge bg-dark ms-2">{{ ucfirst(Auth::user()->role) }}</span>
                </p>
                <p class="text-muted mb-0">Asociación de Motociclismo de Cochabamba</p>
            </div>

            <div class="mt-3 mt-md-0">
                <span class="badge bg-dark px-3 py-2">AMC</span>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-dark text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-white-50 mb-2">Eventos</p>
                                <h2 class="fw-bold mb-1">{{ $events }}</h2>
                                <small class="text-white-50">Eventos registrados</small>
                            </div>
                            <div class="bg-white text-dark rounded-3 p-3">🏁</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-light">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-2">Categorías</p>
                                <h2 class="fw-bold mb-1">{{ $categories }}</h2>
                                <small class="text-muted">Categorías registradas</small>
                            </div>
                            <div class="bg-secondary text-white rounded-3 p-3">📋</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-primary-subtle">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-2">Participantes</p>
                                <h2 class="fw-bold mb-1">{{ $participants }}</h2>
                                <small class="text-muted">Participantes registrados</small>
                            </div>
                            <div class="bg-primary text-white rounded-3 p-3">👥</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-success-subtle">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-2">Inscripciones</p>
                                <h2 class="fw-bold mb-1">{{ $registrations }}</h2>
                                <small class="text-muted">Inscripciones realizadas</small>
                            </div>
                            <div class="bg-success text-white rounded-3 p-3">📝</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">Próximos eventos</h5>
                            <a href="{{ route('eventos.index') }}" class="btn btn-sm btn-dark">Ver eventos</a>
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse($upcomingEvents as $event)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                                <div><strong>{{ $event->title }}</strong><div class="small text-muted">{{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }} · {{ $event->location }}</div></div>
                                <a href="{{ route('eventos.show', $event) }}" class="btn btn-sm btn-outline-dark">Ver</a>
                            </div>
                        @empty
                            <div class="text-center py-5"><div class="fs-1 mb-3">🏁</div><h5 class="fw-semibold">No hay eventos programados</h5><p class="text-muted mb-0">Los próximos eventos aparecerán aquí.</p></div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0">Acciones rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('eventos.index') }}" class="btn btn-dark">🏁 Gestionar eventos</a>
                            <a href="{{ route('eventos.create') }}" class="btn btn-outline-dark">➕ Crear evento</a>
                            <a href="{{ route('categorias.index') }}" class="btn btn-outline-dark">📋 Gestionar categorías</a>
                            <a href="{{ route('participantes.index') }}" class="btn btn-outline-dark">👥 Gestionar participantes</a>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-dark">👤 Gestionar usuarios</a>
                            <a href="{{ route('resultados.index') }}" class="btn btn-outline-dark">🏆 Gestionar resultados</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="fw-bold">Gestión de eventos de motocross</h5>
                        <p class="text-muted mb-md-0">
                            Administra eventos, categorías, participantes, inscripciones y resultados desde una sola plataforma.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <span class="badge bg-dark px-3 py-2">AMC</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>