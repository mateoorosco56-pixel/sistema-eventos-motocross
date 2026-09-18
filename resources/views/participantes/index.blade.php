<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-info mb-2">Gestión</p>
                <h2 class="fw-bold mb-1">Participantes</h2>
                <p class="mb-0 text-muted">Lista de pilotos y competidores</p>
            </div>
            <a href="{{ route('participantes.create') }}" class="btn btn-dark">+ Nuevo participante</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Documento</th>
                                <th>Email</th>
                                <th>Equipo</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($participants as $participant)
                                <tr>
                                    <td>{{ $participant->name }}</td>
                                    <td>{{ $participant->document }}</td>
                                    <td>{{ $participant->email }}</td>
                                    <td>{{ $participant->team ?: 'Sin equipo' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('participantes.edit', $participant) }}" class="btn btn-sm btn-outline-dark">Editar</a>
                                        <form action="{{ route('participantes.destroy', $participant) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Deseas eliminar este participante?')">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        No hay participantes registrados aún.
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
