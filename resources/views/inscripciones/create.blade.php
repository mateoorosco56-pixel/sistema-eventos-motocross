<x-app-layout>
    <div class="workspace-page">
        <header class="workspace-page-heading">
            <div><span class="workspace-eyebrow">Piloto</span><h1>Inscripción al evento</h1><p>Revisa el evento y completa tus datos para participar.</p></div>
            <a href="{{ route('eventos.index') }}" class="btn btn-outline-secondary">Volver a eventos</a>
        </header>

        @if($isPilot)
            <nav class="pilot-flow-steps" aria-label="Pasos de inscripción">
                <a href="{{ route('eventos.index') }}"><span>1</span> Eventos</a>
                <span class="is-current"><span>2</span> Inscripción</span>
                <span><span>3</span> Pago</span>
            </nav>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" role="alert"><strong>No se pudo guardar la inscripción.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('inscripciones.store') }}" class="workspace-registration-layout">
            @csrf
            <aside class="workspace-registration-event">
                <label class="form-label" for="event_id">Evento</label>
                <select name="event_id" id="event_id" class="form-select" required>
                    <option value="">Selecciona un evento</option>
                    @foreach($events as $event)
                        @php
                            $eventCategories = $event->categories->map(fn ($category) => [
                                'id' => $category->id,
                                'name' => $category->name,
                                'entry_fee' => $category->pivot->entry_fee ?? $event->entry_fee,
                            ]);
                            if ($event->category && !$eventCategories->contains('id', $event->category->id)) {
                                $eventCategories->push([
                                    'id' => $event->category->id,
                                    'name' => $event->category->name,
                                    'entry_fee' => $event->entry_fee,
                                ]);
                            }
                        @endphp
                        <option value="{{ $event->id }}"
                            data-categories='@json($eventCategories->values())'
                            data-occupied-dorsals='@json($occupiedDorsals[$event->id] ?? [])'
                            data-title="{{ $event->title }}"
                            data-date="{{ $event->event_date?->format('d/m/Y') }}"
                            data-time="{{ $event->start_time ? substr($event->start_time, 0, 5) : 'Hora por confirmar' }}"
                            data-location="{{ $event->location }}"
                            data-fee="{{ $event->entry_fee !== null ? 'Bs. '.number_format((float) $event->entry_fee, 2) : 'Sin costo especificado' }}"
                            data-cover="{{ $event->cover_path ? url('/storage/'.$event->cover_path) : '' }}"
                            @selected((string) old('event_id', $selectedEventId ?? '') === (string) $event->id)>
                            {{ $event->title }}
                        </option>
                    @endforeach
                </select>

                <div id="workspace-event-preview" class="workspace-registration-preview">
                    @if($selectedEvent)
                        @if($selectedEvent->cover_path)
                            <img src="{{ url('/storage/'.$selectedEvent->cover_path) }}" alt="Portada de {{ $selectedEvent->title }}" data-preview-image>
                        @else
                            <div class="workspace-registration-placeholder" data-preview-placeholder>AMC</div>
                        @endif
                        <div class="workspace-registration-preview-content">
                            <h2 data-preview-title>{{ $selectedEvent->title }}</h2>
                            <div class="workspace-registration-preview-meta"><span>▣ {{ $selectedEvent->event_date?->format('d/m/Y') }}</span><span>◷ {{ $selectedEvent->start_time ? substr($selectedEvent->start_time, 0, 5) : 'Hora por confirmar' }}</span><span>⌖ {{ $selectedEvent->location }}</span></div>
                            <div class="workspace-registration-preview-fee">{{ $categories->isNotEmpty() ? 'Selecciona una categoría para ver el costo' : ($selectedEvent->entry_fee !== null ? 'Bs. '.number_format((float) $selectedEvent->entry_fee, 2) : 'Sin costo especificado') }}</div>
                            <div class="workspace-registration-preview-categories" data-preview-categories>
                                @foreach($categories as $category)<span>{{ $category->name }}</span>@endforeach
                            </div>
                        </div>
                    @else
                        <div class="workspace-registration-placeholder" data-preview-placeholder>AMC</div>
                        <div class="workspace-registration-preview-content"><h2 data-preview-title>Selecciona un evento</h2><div class="workspace-registration-preview-meta" data-preview-meta></div><div class="workspace-registration-preview-fee" data-preview-fee></div><div class="workspace-registration-preview-categories" data-preview-categories></div></div>
                    @endif
                </div>

                @if(!$isPilot)
                    <div class="row g-3 mt-1">
                        <div class="col-12"><label class="form-label">Categorías</label><div class="small text-muted">El organizador registrará al piloto en las categorías disponibles.</div></div>
                    </div>
                @endif
            </aside>

            <section class="workspace-registration-form">
                <div class="workspace-panel-heading"><span class="workspace-panel-icon">01</span><div><h2>Datos del piloto</h2><p>Completa la información que usaremos para tu registro.</p></div></div>
                <div class="row g-3">
                    @if(!$isPilot)
                        <div class="col-md-6">
                            <label class="form-label">Participante</label>
                            <select name="participant_id" class="form-select" required><option value="">Selecciona un participante</option>@foreach($participants as $participant)<option value="{{ $participant->id }}" @selected((string) old('participant_id') === (string) $participant->id)>{{ $participant->name }}</option>@endforeach</select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select" required><option value="pendiente" @selected(old('status', 'pendiente') === 'pendiente')>Pendiente</option><option value="confirmada" @selected(old('status') === 'confirmada')>Confirmada</option><option value="rechazada" @selected(old('status') === 'rechazada')>Rechazada</option></select>
                        </div>
                    @else
                        <div class="col-md-6"><label class="form-label">Nombre completo</label><input type="text" name="name" class="form-control" value="{{ old('name', auth()->user()->name) }}" required></div>
                        <div class="col-md-6"><label class="form-label">Cédula de identidad</label><input type="text" name="document" class="form-control" value="{{ old('document') }}" placeholder="Ej. 12345678" required></div>
                        <div class="col-md-6"><label class="form-label">Correo electrónico</label><input type="email" name="email" class="form-control" value="{{ old('email', auth()->user()->email) }}" required></div>
                        <div class="col-md-6"><label class="form-label">Teléfono</label><input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="Ej. 78912345" required></div>
                        <div class="col-md-6"><label class="form-label">Equipo</label><input type="text" name="team" class="form-control" value="{{ old('team') }}" placeholder="Ej. Equipo AMC"></div>
                        <div class="col-md-6">
                            <label class="form-label">Categoría</label>
                            @if($categories->isEmpty())<div class="alert alert-warning mb-2">Selecciona un evento con categorías disponibles.</div>@endif
                            <select name="category_id" id="category_id" class="form-select" required @disabled($categories->isEmpty())><option value="">Selecciona una categoría</option>@foreach($categories as $category)@php($categoryFee = $category->pivot->entry_fee ?? $selectedEvent?->entry_fee)<option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}{{ $categoryFee !== null ? ' — Bs. '.number_format((float) $categoryFee, 2) : '' }}</option>@endforeach</select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dorsal</label>
                            <select name="dorsal" id="dorsal" class="form-select" required><option value="">Selecciona un dorsal</option></select>
                            @error('dorsal')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    @endif
                </div>

                @if($isPilot)
                    <div class="workspace-registration-notice"><span aria-hidden="true">ⓘ</span> Tu inscripción se enviará como solicitud pendiente para <strong>{{ auth()->user()->name }}</strong>.</div>
                @endif

                <div class="workspace-form-actions">
                    <a href="{{ route(auth()->user()->role === 'piloto' ? 'eventos.index' : 'inscripciones.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">{{ $isPilot ? 'Continuar al pago' : 'Guardar inscripción' }}</button>
                </div>
            </section>
        </form>
    </div>
</x-app-layout>

@if($isPilot)
<script>
    const eventSelect = document.getElementById('event_id');
    const categorySelect = document.getElementById('category_id');
    const dorsalSelect = document.getElementById('dorsal');
    const eventPreview = document.getElementById('workspace-event-preview');
    const oldDorsal = @json(old('dorsal'));
    const oldCategory = @json(old('category_id'));

    function updateEventPreview() {
        const selectedEvent = eventSelect.options[eventSelect.selectedIndex];
        const title = eventPreview.querySelector('[data-preview-title]');
        const meta = eventPreview.querySelector('[data-preview-meta]') || eventPreview.querySelector('.workspace-registration-preview-meta');
        const fee = eventPreview.querySelector('[data-preview-fee]') || eventPreview.querySelector('.workspace-registration-preview-fee');
        const categoriesContainer = eventPreview.querySelector('[data-preview-categories]');

        if (!selectedEvent || !selectedEvent.value) {
            title.textContent = 'Selecciona un evento';
            meta.replaceChildren();
            fee.textContent = '';
            categoriesContainer.replaceChildren();
            const currentImage = eventPreview.querySelector('[data-preview-image]');
            if (currentImage) {
                currentImage.remove();
            }
            if (!eventPreview.querySelector('[data-preview-placeholder]')) {
                const placeholder = document.createElement('div');
                placeholder.className = 'workspace-registration-placeholder';
                placeholder.dataset.previewPlaceholder = '';
                placeholder.textContent = 'AMC';
                eventPreview.prepend(placeholder);
            }
            return;
        }

        title.textContent = selectedEvent.dataset.title;
        const categories = JSON.parse(selectedEvent.dataset.categories || '[]');
        const selectedCategory = categories.find(category => String(category.id) === String(categorySelect.value));
        const categoryFee = selectedCategory?.entry_fee;
        fee.textContent = selectedCategory
            ? (categoryFee !== null && categoryFee !== undefined
                ? `Bs. ${Number(categoryFee).toFixed(2)}`
                : 'Sin costo especificado')
            : (categories.length ? 'Selecciona una categoría para ver el costo' : selectedEvent.dataset.fee);
        meta.replaceChildren();
        [selectedEvent.dataset.date, selectedEvent.dataset.time, selectedEvent.dataset.location].forEach((value, index) => {
            const item = document.createElement('span');
            item.textContent = `${['▣', '◷', '⌖'][index]} ${value}`;
            meta.append(item);
        });

        categoriesContainer.replaceChildren();
        categories.forEach(category => {
            const item = document.createElement('span');
            item.textContent = category.name;
            categoriesContainer.append(item);
        });

        const coverUrl = selectedEvent.dataset.cover;
        let image = eventPreview.querySelector('[data-preview-image]');
        let placeholder = eventPreview.querySelector('[data-preview-placeholder]');
        if (coverUrl) {
            if (!image) {
                image = document.createElement('img');
                image.dataset.previewImage = '';
                eventPreview.prepend(image);
            }
            image.src = coverUrl;
            image.alt = `Portada de ${selectedEvent.dataset.title}`;
            placeholder?.remove();
        } else {
            image?.remove();
            if (!placeholder) {
                placeholder = document.createElement('div');
                placeholder.className = 'workspace-registration-placeholder';
                placeholder.dataset.previewPlaceholder = '';
                placeholder.textContent = 'AMC';
                eventPreview.prepend(placeholder);
            }
        }
    }

    function updateCategories() {
        const selectedEvent = eventSelect.options[eventSelect.selectedIndex];
        const categories = selectedEvent ? JSON.parse(selectedEvent.dataset.categories || '[]') : [];
        const occupiedByCategory = selectedEvent ? JSON.parse(selectedEvent.dataset.occupiedDorsals || '{}') : {};
        categorySelect.innerHTML = '<option value="">Selecciona una categoría</option>';
        categories.forEach(category => {
            const fee = category.entry_fee !== null && category.entry_fee !== undefined
                ? ` — Bs. ${Number(category.entry_fee).toFixed(2)}`
                : '';
            categorySelect.add(new Option(`${category.name}${fee}`, category.id));
        });
        categorySelect.disabled = categories.length === 0;

        if (oldCategory && categories.some(category => String(category.id) === String(oldCategory))) {
            categorySelect.value = oldCategory;
        }

        updateDorsals(occupiedByCategory);
        updateEventPreview();
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
        updateEventPreview();
    });
    updateCategories();
</script>
@endif
