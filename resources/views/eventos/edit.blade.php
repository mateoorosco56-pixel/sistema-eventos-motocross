<x-app-layout>
    <div class="container py-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Editar evento</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('eventos.update', $event) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Título</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $event->title) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="event_date" class="form-control" value="{{ old('event_date', $event->event_date) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Categorías disponibles</label>
                            @php($selectedCategories = old('category_ids', $event->categories->pluck('id')->all()))
                            <div class="dropdown" data-bs-auto-close="outside">
                                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Selecciona las categorías
                                </button>
                                <div class="dropdown-menu w-100 p-3 amc-category-menu">
                                    @foreach ($categories as $category)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="category_ids[]" value="{{ $category->id }}" id="edit-category-{{ $category->id }}" @checked(in_array($category->id, $selectedCategories))>
                                            <label class="form-check-label" for="edit-category-{{ $category->id }}">{{ $category->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <small class="text-muted">Puedes seleccionar una o varias categorías.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ubicación</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location', $event->location) }}" required>
                        </div>

                        @include('eventos.partials.map-picker', ['latitude' => $event->latitude, 'longitude' => $event->longitude])

                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select" required>
                                @foreach (['programado', 'en_curso', 'finalizado', 'cancelado'] as $status)
                                    <option value="{{ $status }}" {{ old('status', $event->status) === $status ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Costo de inscripción</label>
                            <input type="number" name="entry_fee" class="form-control" min="0" step="0.01" value="{{ old('entry_fee', $event->entry_fee) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Código QR para el pago</label>
                            <input type="file" name="payment_qr" class="form-control" accept="image/jpeg,image/png,image/webp">
                            @if($event->payment_qr_path)
                                <img src="{{ url('/storage/'.$event->payment_qr_path) }}" alt="QR de pago actual" class="mt-2" style="max-width: 150px;">
                            @endif
                        </div>

                        <div class="col-12">
                            <label class="form-label">Instrucciones de pago</label>
                            <textarea name="payment_instructions" rows="3" class="form-control">{{ old('payment_instructions', $event->payment_instructions) }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" rows="5" class="form-control">{{ old('description', $event->description) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex flex-wrap gap-2 form-actions">
                        <button type="submit" class="btn btn-dark">Actualizar evento</button>
                        <a href="{{ route('eventos.index') }}" class="btn btn-outline-dark">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
