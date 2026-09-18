<x-app-layout>
    <div class="container-fluid py-4">
        <h2 class="fw-bold mb-1">Gestión de usuarios</h2>
        <p class="text-muted mb-4">Administra cuentas y roles del sistema.</p>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if($errors->has('user')) <div class="alert alert-danger">{{ $errors->first('user') }}</div> @endif

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark"><tr><th>Usuario</th><th>Rol</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $user->name }}<br><small class="text-muted">{{ $user->email }}</small></td>
                                <td>
                                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="d-flex gap-2 align-items-start justify-content-end justify-content-md-start">
                                        @csrf @method('PATCH')
                                        <select name="role" class="form-select form-select-sm" style="max-width: 190px">
                                            @foreach(['usuario', 'piloto', 'organizador', 'administrador'] as $role)
                                                <option value="{{ $role }}" @selected($user->role === $role)>{{ ucfirst($role) }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-outline-dark">Guardar</button>
                                    </form>
                                    @if($user->role === 'piloto')
                                        <div class="mt-2 d-flex gap-2 flex-wrap">
                                            @if($user->document_path)
                                                <a href="{{ route('admin.users.document', $user) }}" target="_blank" class="btn btn-sm btn-outline-primary">Documento</a>
                                            @endif
                                            @if($user->selfie_path)
                                                <a href="{{ route('admin.users.selfie', $user) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Selfie</a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if(!$user->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Deseas eliminar este usuario?')">Eliminar</button></form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>