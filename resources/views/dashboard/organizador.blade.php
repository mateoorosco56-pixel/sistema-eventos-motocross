<x-app-layout>
    <div class="container-fluid py-4">
        <section class="rounded-4 p-4 p-lg-5 mb-4 text-white" style="background: linear-gradient(120deg, #3b1f0f 0%, #8a4b16 55%, #d18a32 100%);">
            <div class="row align-items-end g-4">
                <div class="col-lg-8">
                    <p class="text-uppercase small fw-semibold mb-2 opacity-75">Centro de operaciones</p>
                    <h1 class="display-6 fw-bold mb-2">Tu campeonato, en marcha.</h1>
                    <p class="lead mb-0">Hola, {{ Auth::user()->name }}. Organiza cada fecha desde un solo lugar.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('eventos.create') }}" class="btn btn-light fw-semibold">+ Crear evento</a>
                </div>
            </div>
        </section>

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="border-start border-4 border-warning bg-white p-3 shadow-sm h-100">
                    <small class="text-muted d-block">Eventos creados</small>
                    <strong class="fs-2">{{ $events }}</strong>
                    <span class="d-block small text-muted">Calendario activo</span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="border-start border-4 border-danger bg-white p-3 shadow-sm h-100">
                    <small class="text-muted d-block">Solicitudes</small>
                    <strong class="fs-2">{{ $pendingRegistrations }}</strong>
                    <span class="d-block small text-muted">Requieren revisión</span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="border-start border-4 border-primary bg-white p-3 shadow-sm h-100">
                    <small class="text-muted d-block">Participantes</small>
                    <strong class="fs-2">{{ $participants }}</strong>
                    <span class="d-block small text-muted">En el registro</span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="border-start border-4 border-success bg-white p-3 shadow-sm h-100">
                    <small class="text-muted d-block">Resultados</small>
                    <strong class="fs-2">{{ $updatedResults }}</strong>
                    <span class="d-block small text-muted">Ya actualizados</span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="bg-white shadow-sm p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <p class="text-uppercase small text-warning fw-semibold mb-1">Flujo de trabajo</p>
                            <h4 class="fw-bold mb-0">Siguientes pasos</h4>
                        </div>
                        <span class="badge rounded-pill text-bg-light">AMC</span>
                    </div>
                    <div class="d-flex gap-3 border-bottom pb-3 mb-3">
                        <span class="badge rounded-circle text-bg-warning fs-6 align-self-start">1</span>
                        <div><strong>Revisa las inscripciones</strong><p class="text-muted small mb-0">Confirma o rechaza las solicitudes pendientes.</p></div>
                    </div>
                    <div class="d-flex gap-3 border-bottom pb-3 mb-3">
                        <span class="badge rounded-circle text-bg-primary fs-6 align-self-start">2</span>
                        <div><strong>Prepara la competencia</strong><p class="text-muted small mb-0">Mantén actualizados tus eventos y participantes.</p></div>
                    </div>
                    <div class="d-flex gap-3">
                        <span class="badge rounded-circle text-bg-success fs-6 align-self-start">3</span>
                        <div><strong>Publica los resultados</strong><p class="text-muted small mb-0">Registra posiciones y puntos al finalizar.</p></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="bg-dark text-white shadow-sm p-4 h-100">
                    <p class="text-uppercase small text-warning fw-semibold mb-1">Accesos</p>
                    <h4 class="fw-bold mb-4">Administrar</h4>
                    <div class="d-grid gap-2">
                        <a href="{{ route('organizador.mis-eventos') }}" class="btn btn-warning text-dark text-start">🏁 Mis eventos</a>
                        <a href="{{ route('inscripciones.index') }}" class="btn btn-outline-light text-start">📝 Inscripciones</a>
                        <a href="{{ route('resultados.index') }}" class="btn btn-outline-light text-start">🏆 Resultados</a>
                        <a href="{{ route('participantes.index') }}" class="btn btn-outline-light text-start">👥 Participantes</a>
                    </div>

                    <div class="mt-4 p-3 rounded-3 bg-white bg-opacity-10 border border-light border-opacity-10">
                        <small class="text-uppercase text-warning fw-semibold">Estado</small>
                        <p class="mb-0 mt-2 small text-white-50">Los cambios que hagas en resultados e inscripciones se reflejan de inmediato para todos los usuarios.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
