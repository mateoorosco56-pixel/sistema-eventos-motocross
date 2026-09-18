<x-app-layout>
    <div class="container py-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Editar participante</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('participantes.update', $participant) }}">
                    @csrf
                    @method('PATCH')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $participant->name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Documento</label>
                            <input type="text" name="document" class="form-control" value="{{ old('document', $participant->document) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $participant->email) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $participant->phone) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Licencia</label>
                            <input type="text" name="license" class="form-control" value="{{ old('license', $participant->license) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Equipo</label>
                            <input type="text" name="team" class="form-control" value="{{ old('team', $participant->team) }}">
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-dark">Actualizar participante</button>
                        <a href="{{ route('participantes.index') }}" class="btn btn-outline-dark">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
