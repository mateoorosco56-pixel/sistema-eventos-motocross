<x-app-layout>
    <div class="container py-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Nueva inscripción</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('inscripciones.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Evento</label>
                            <select name="event_id" id="event_id" class="form-select" required>
                                <option value="">Selecciona un evento</option>
                                @foreach ($events as $event)
                                    @php
                                        $eventCategories = $event->categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name]);
                                        if ($event->category && !$eventCategories->contains('id', $event->category->id)) {
                                            $eventCategories->push(['id' => $event->category->id, 'name' => $event->category->name]);
                                        }
                                    @endphp
                                    <option value="{{ $event->id }}" data-categories='@json($eventCategories->values())' data-occupied-dorsals='@json($occupiedDorsals[$event->id] ?? [])' @selected((string) old('event_id', $selectedEventId ?? '') === (string) $event->id)>
                                        {{ $event->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if(!$isPilot)
                            <div class="col-md-6">
                                <label class="form-label">Participante</label>
                                <select name="participant_id" class="form-select" required>
                                    <option value="">Selecciona un participante</option>
                                    @foreach ($participants as $participant)
                                        <option value="{{ $participant->id }}">{{ $participant->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <select name="status" class="form-select" required>
                                    <option value="pendiente" selected>Pendiente</option>
                                    <option value="confirmada">Confirmada</option>
                                    <option value="rechazada">Rechazada</option>
                                </select>
                            </div>
                        @else
                            <div class="col-md-6">
                                <label class="form-label">Nombre completo</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', auth()->user()->name) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Documento</label>
                                <input type="text" name="document" class="form-control" value="{{ old('document') }}" placeholder="Ej. 12345678" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Correo electrónico</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', auth()->user()->email) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="Ej. 78912345" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Equipo</label>
                                <input type="text" name="team" class="form-control" value="{{ old('team') }}" placeholder="Ej. Equipo AMC">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Categoría a participar</label>
                                @if($categories->isEmpty())
                                    <div class="alert alert-warning mb-0">No hay categorías disponibles. Solicita al administrador u organizador que registre una categoría.</div>
                                @endif
                                <select name="category_id" id="category_id" class="form-select" required @disabled($categories->isEmpty())>
                                    <option value="">Selecciona una categoría</option>
                                    @foreach ($categories ?? [] as $category)
                                        <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Número a participar</label>
                                <select name="dorsal" id="dorsal" class="form-select" required>
                                    <option value="">Selecciona un dorsal</option>
                                </select>
                                @error('dorsal')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="alert alert-light border mb-0">
                                    La inscripción será enviada como solicitud pendiente para <strong>{{ auth()->user()->name }}</strong>.
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 d-flex flex-wrap gap-2 form-actions">
                        <button type="submit" class="btn btn-dark">Guardar inscripción</button>
                        <a href="{{ route(auth()->user()->role === 'piloto' ? 'eventos.index' : 'inscripciones.index') }}" class="btn btn-outline-dark">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

@if($isPilot)
<script>
    const eventSelect = document.getElementById('event_id');
    const categorySelect = document.getElementById('category_id');
    const dorsalSelect = document.getElementById('dorsal');
    const oldDorsal = @json(old('dorsal'));
    const oldCategory = @json(old('category_id'));

    function updateCategories() {
        const selectedEvent = eventSelect.options[eventSelect.selectedIndex];
        const categories = selectedEvent ? JSON.parse(selectedEvent.dataset.categories || '[]') : [];
        const occupiedByCategory = selectedEvent ? JSON.parse(selectedEvent.dataset.occupiedDorsals || '{}') : {};
        categorySelect.innerHTML = '<option value="">Selecciona una categoría</option>';
        categories.forEach(category => categorySelect.add(new Option(category.name, category.id)));
        categorySelect.disabled = categories.length === 0;

        if (oldCategory && categories.some(category => String(category.id) === String(oldCategory))) {
            categorySelect.value = oldCategory;
        }

        updateDorsals(occupiedByCategory);
    }

    function updateDorsals(occupiedByCategory = {}) {
        const occupied = (occupiedByCategory[String(categorySelect.value)] || []).map(Number);
        dorsalSelect.innerHTML = '<option value="">Selecciona un dorsal</option>';

        for (let dorsal = 1; dorsal <= 99; dorsal++) {
            const option = new Option(String(dorsal).padStart(2, '0'), dorsal);
            if (occupied.includes(dorsal)) {
                option.disabled = true;
                option.text = `${String(dorsal).padStart(2, '0')} - Ocupado`;
            }
            if (String(oldDorsal) === String(dorsal)) {
                option.selected = true;
            }
            dorsalSelect.add(option);
        }
    }

    eventSelect.addEventListener('change', updateCategories);
    categorySelect.addEventListener('change', () => {
        const selectedEvent = eventSelect.options[eventSelect.selectedIndex];
        const occupiedByCategory = selectedEvent ? JSON.parse(selectedEvent.dataset.occupiedDorsals || '{}') : {};
        updateDorsals(occupiedByCategory);
    });
    updateCategories();
</script>
@endif
