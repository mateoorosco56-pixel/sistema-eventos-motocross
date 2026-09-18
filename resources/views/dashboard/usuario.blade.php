<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-success mb-2">Usuario</p>
                <h2 class="fw-bold mb-1">Portal del usuario</h2>
                <p class="mb-0 text-muted">Bienvenido, {{ Auth::user()->name }}</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-success-subtle">
                    <div class="card-body">
                        <p class="text-muted mb-2">Eventos</p>
                        <h3 class="fw-bold mb-1">{{ $availableEvents }}</h3>
                        <small class="text-muted">Disponibles</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 bg-primary-subtle">
                    <div class="card-body">
                        <p class="text-muted mb-2">Perfil</p>
                        <h3 class="fw-bold mb-1">1</h3>
                        <small class="text-muted">Cuenta activa</small>
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
                    <a href="{{ route('eventos.index') }}" class="btn btn-success">🏁 Ver eventos</a>
                    <a href="{{ route('resultados.index') }}" class="btn btn-outline-success">🏆 Ver resultados</a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Explora la competencia</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0">Consulta los próximos eventos de motocross.</li>
                    <li class="list-group-item px-0">Consulta los resultados publicados de las competencias.</li>
                </ul>
            </div>
        </div>

    </div>
</x-app-layout>
