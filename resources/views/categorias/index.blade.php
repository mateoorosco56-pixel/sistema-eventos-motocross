<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-info mb-2">Gestión</p>
                <h2 class="fw-bold mb-1">Categorías</h2>
                <p class="mb-0 text-muted">Lista de categorías de competencias</p>
            </div>
            @if(auth()->check() && in_array(auth()->user()->role, ['organizador', 'administrador', 'piloto']))
                <a href="{{ route('categorias.create') }}" class="btn btn-dark">+ Nueva categoría</a>
            @endif
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
                                <th>Descripción</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                                <tr>
                                    <td>{{ $category->name }}</td>
                                    <td>{{ $category->description ?: 'Sin descripción' }}</td>
                                    @if(auth()->check() && in_array(auth()->user()->role, ['organizador', 'administrador', 'piloto']))
                                        <td class="text-end">
                                            <a href="{{ route('categorias.edit', $category) }}" class="btn btn-sm btn-outline-dark">Editar</a>
                                            <form action="{{ route('categorias.destroy', $category) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Deseas eliminar esta categoría?')">Eliminar</button>
                                            </form>
                                        </td>
                                    @else
                                        <td class="text-end text-muted">Consulta</td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        No hay categorías registradas aún.
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
