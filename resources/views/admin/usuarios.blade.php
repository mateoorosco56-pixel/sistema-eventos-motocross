<x-app-layout>
    <div class="container-fluid py-4">
        <h2 class="fw-bold mb-1">Gestión de usuarios</h2>
        <p class="text-muted mb-4">Administra cuentas, roles y revisiones de identidad de pilotos.</p>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if($errors->has('user')) <div class="alert alert-danger">{{ $errors->first('user') }}</div> @endif

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark"><tr><th>Usuario e identidad</th><th>Rol y revisión</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>
                                    {{ $user->name }}<br><small class="text-muted">{{ $user->email }}</small>
                                    @if($user->role === 'piloto')
                                        <div class="small mt-2">
                                            <strong>Carnet:</strong> {{ $user->identity_document_number ?: 'No registrado' }}<br>
                                            <strong>Estado:</strong>
                                            @switch($user->verification_status)
                                                @case('verified') Verificada @break
                                                @case('rejected') Rechazada @break
                                                @default Pendiente de revisión
                                            @endswitch
                                            @if($user->verification_method)
                                                <br><strong>Método:</strong>
                                                @switch($user->verification_method)
                                                    @case('automatic_ocr_face') Automático (OCR + comparación facial) @break
                                                    @case('automatic_review') Revisión automática enviada a revisión manual @break
                                                    @case('manual_admin') Revisión manual del administrador @break
                                                    @case('legacy_review_required') Verificación heredada, requiere nueva revisión @break
                                                    @default {{ $user->verification_method }}
                                                @endswitch
                                            @endif
                                            @if($user->verification_reviewed_at)
                                                <br><strong>Fecha de revisión:</strong> {{ $user->verification_reviewed_at->format('d/m/Y H:i') }}
                                            @elseif($user->verification_method === 'legacy_review_required' && $user->verified_at)
                                                <br><strong>Fecha de verificación anterior:</strong> {{ $user->verified_at->format('d/m/Y H:i') }}
                                            @endif
                                            @if($user->verificationReviewer)
                                                <br><strong>Revisado por:</strong> {{ $user->verificationReviewer->name }}
                                            @endif
                                            @if($user->verification_result)
                                                <br><strong>Resultado automático:</strong> {{ $user->verification_result }}
                                            @endif
                                            @if($user->verification_rejection_reason)
                                                <br><strong>Motivo de rechazo:</strong> {{ $user->verification_rejection_reason }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
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
                                        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-2 d-grid gap-2">
                                            @csrf @method('PATCH')
                                            <select name="verification_status" class="form-select form-select-sm" aria-label="Estado de verificación">
                                                <option value="pending_review" @selected($user->verification_status === 'pending_review')>Pendiente de revisión</option>
                                                <option value="verified" @selected($user->verification_status === 'verified')>Verificada</option>
                                                <option value="rejected" @selected($user->verification_status === 'rejected')>Rechazada</option>
                                            </select>
                                            <textarea name="verification_rejection_reason" class="form-control form-control-sm" maxlength="1000" placeholder="Motivo obligatorio si se rechaza">{{ old('verification_rejection_reason', $user->verification_rejection_reason) }}</textarea>
                                            @error('verification_rejection_reason') <small class="text-danger">{{ $message }}</small> @enderror
                                            <button class="btn btn-sm btn-outline-success">Guardar revisión de identidad</button>
                                        </form>
                                        <div class="mt-2 d-flex gap-2 flex-wrap">
                                            @if($user->document_path)
                                                <a href="{{ route('admin.users.document', $user) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">Carnet</a>
                                            @endif
                                            @if($user->document_back_path)
                                                <a href="{{ route('admin.users.document-back', $user) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">Reverso del carnet</a>
                                            @endif
                                            @if($user->selfie_path)
                                                <a href="{{ route('admin.users.selfie', $user) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">Rostro</a>
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