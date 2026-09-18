<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-success mb-2">Gestión</p>
                <h2 class="fw-bold mb-1">Inscripciones</h2>
                <p class="mb-0 text-muted">Lista de inscripciones a eventos</p>
            </div>
            @if(in_array(auth()->user()->role, ['organizador', 'administrador']))
                <a href="{{ route('inscripciones.create') }}" class="btn btn-dark">+ Nueva inscripción</a>
            @endif
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Evento</th>
                                <th>Piloto</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($registrations as $registration)
                                <tr>
                                    <td>{{ $registration->event->title ?? 'Evento' }}</td>
                                    <td>{{ $registration->participant->name ?? 'Participante' }}</td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ ucfirst($registration->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if($registration->status === 'pendiente' && in_array(auth()->user()->role, ['organizador', 'administrador']))
                                            <form action="{{ route('inscripciones.update', $registration) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="confirmada">
                                                <button type="submit" class="btn btn-sm btn-success">Aceptar</button>
                                            </form>
                                            <form action="{{ route('inscripciones.update', $registration) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="rechazada">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Rechazar</button>
                                            </form>
                                        @elseif(in_array(auth()->user()->role, ['organizador', 'administrador']))
                                            <a href="{{ route('inscripciones.edit', $registration) }}" class="btn btn-sm btn-outline-dark">Editar</a>
                                            <form action="{{ route('inscripciones.destroy', $registration) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Deseas eliminar esta inscripción?')">Eliminar</button>
                                            </form>
                                        @else
                                            <a href="{{ route('piloto.inscripciones.show', $registration) }}" class="btn btn-sm btn-outline-primary">Ver detalles</a>
                                            @if($registration->status === 'pendiente')
                                                <form action="{{ route('piloto.inscripciones.cancel', $registration) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Deseas cancelar esta inscripción?')">Cancelar inscripción</button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        No hay inscripciones registradas aún.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
