<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-primary mb-2">Competición</p>
                <h2 class="fw-bold mb-1">Panel del piloto</h2>
                <p class="mb-0 text-muted">Bienvenido, {{ Auth::user()->name }}</p>
            </div>
            <span class="badge bg-primary px-3 py-2">PILOTO</span>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-primary-subtle">
                    <div class="card-body">
                        <p class="text-muted mb-2">Próximos eventos</p>
                        <h3 class="fw-bold mb-1">{{ $upcomingEvents ?? 0 }}</h3>
                        <small class="text-muted">Disponibles</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-light">
                    <div class="card-body">
                        <p class="text-muted mb-2">Inscripciones</p>
                        <h3 class="fw-bold mb-1">{{ $activeRegistrations ?? 0 }}</h3>
                        <small class="text-muted">Activas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-success-subtle">
                    <div class="card-body">
                        <p class="text-muted mb-2">Resultados</p>
                        <h3 class="fw-bold mb-1">{{ $resultCount ?? 0 }}</h3>
                        <small class="text-muted">Historial</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="fw-bold mb-0">Acciones rápidas</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('eventos.index') }}" class="btn btn-primary">🏁 Ver eventos</a>
                    <a href="{{ route('piloto.mis-eventos') }}" class="btn btn-outline-primary">🏎️ Mis eventos</a>
                    <a href="{{ route('inscripciones.index') }}" class="btn btn-outline-primary">📝 Mis inscripciones</a>
                    <a href="{{ route('piloto.mis-eventos') }}" class="btn btn-outline-primary">🏆 Resultados y ranking</a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Tu actividad</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0">Consulta los eventos disponibles para competir.</li>
                    <li class="list-group-item px-0">Gestiona tus inscripciones desde una sola vista.</li>
                    <li class="list-group-item px-0">Revisa tu historial de resultados cuando esté disponible.</li>
                </ul>
            </div>
        </div>

    </div>
</x-app-layout>
