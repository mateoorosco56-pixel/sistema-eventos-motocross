<x-app-layout>
    <div class="container py-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Crear categoría</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('categorias.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" rows="5" class="form-control"></textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-dark">Guardar categoría</button>
                        <a href="{{ route('categorias.index') }}" class="btn btn-outline-dark">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
