<x-app-layout>
    <div class="container-fluid py-4">
        <h2 class="fw-bold mb-1">Solicitudes de organizador</h2>
        <p class="text-muted mb-4">Revisa y decide los cambios de rol pendientes.</p>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark"><tr><th>Usuario</th><th>Motivo</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        @forelse($requests as $organizerRequest)
                            <tr>
                                <td>{{ $organizerRequest->user->name }}<br><small class="text-muted">{{ $organizerRequest->user->email }}</small></td>
                                <td>{{ $organizerRequest->reason ?: 'Sin motivo indicado' }}</td>
                                <td><span class="badge bg-secondary">{{ ucfirst($organizerRequest->status) }}</span></td>
                                <td class="text-end">
                                    @if($organizerRequest->status === 'pendiente')
                                        <form method="POST" action="{{ route('admin.organizer-requests.update', $organizerRequest) }}" class="d-inline">@csrf @method('PATCH')<input type="hidden" name="status" value="aprobada"><button class="btn btn-sm btn-success">Aprobar</button></form>
                                        <form method="POST" action="{{ route('admin.organizer-requests.update', $organizerRequest) }}" class="d-inline">@csrf @method('PATCH')<input type="hidden" name="status" value="rechazada"><button class="btn btn-sm btn-outline-danger">Rechazar</button></form>
                                    @else
                                        <span class="text-muted small">{{ $organizerRequest->reviewed_at?->format('d/m/Y H:i') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">No hay solicitudes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>