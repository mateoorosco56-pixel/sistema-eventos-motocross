<x-app-layout>
    <div class="container py-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Crear evento</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('eventos.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Título del evento</label>
                            <input type="text" name="title" class="form-control" placeholder="Ej. Copa AMC Motocross" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Categorías disponibles</label>
                            <div class="dropdown" data-bs-auto-close="outside">
                                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Selecciona las categorías
                                </button>
                                <div class="dropdown-menu w-100 p-3 amc-category-menu">
                                    @foreach ($categories as $category)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="category_ids[]" value="{{ $category->id }}" id="category-{{ $category->id }}" @checked(in_array($category->id, old('category_ids', [])))>
                                            <label class="form-check-label" for="category-{{ $category->id }}">{{ $category->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <small class="text-muted">Puedes seleccionar una o varias categorías.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ubicación</label>
                            <input type="text" name="location" class="form-control" placeholder="Ej. Circuito La Paz" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fecha del evento</label>
                            <input type="date" name="event_date" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <div class="row g-3 align-items-start">
                                @include('eventos.partials.map-picker', ['latitude' => null, 'longitude' => null])

                                <div class="col-md-6">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Costo de inscripción</label>
                                            <input type="number" name="entry_fee" class="form-control" min="0" step="0.01" placeholder="Ej. 150">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Código QR para el pago</label>
                                            <input type="file" name="payment_qr" class="form-control" accept="image/jpeg,image/png,image/webp">
                                            <small class="text-muted">Imagen JPG, PNG o WEBP de máximo 4 MB.</small>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Instrucciones de pago</label>
                                            <textarea name="payment_instructions" rows="3" class="form-control" placeholder="Indica el monto, banco, titular o referencia que debe usar el piloto.">{{ old('payment_instructions') }}</textarea>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Fecha límite de inscripción</label>
                                            <input type="date" name="registration_deadline" class="form-control">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Cupo máximo</label>
                                            <input type="number" name="max_participants" class="form-control" min="1" placeholder="Ej. 40">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Estado</label>
                                            <select name="status" class="form-select" required>
                                                <option value="programado">Programado</option>
                                                <option value="en_curso">En curso</option>
                                                <option value="finalizado">Finalizado</option>
                                                <option value="cancelado">Cancelado</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" rows="5" class="form-control" placeholder="Describe la competencia, reglas, nivel, premios u observaciones importantes."></textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex flex-wrap gap-2 form-actions">
                        <button type="submit" class="btn btn-dark">Guardar evento</button>
                        <a href="{{ route('eventos.index') }}" class="btn btn-outline-dark">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
