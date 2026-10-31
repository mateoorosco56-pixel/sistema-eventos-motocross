<x-app-layout>
    <div class="workspace-page">
        <header class="workspace-page-heading">
            <div><span class="workspace-eyebrow">Organizador</span><h1>Crear evento o campeonato</h1><p>Publica un evento individual o genera automáticamente las fechas de un campeonato.</p></div>
            <a href="{{ route('eventos.index') }}" class="btn btn-outline-secondary">Volver a Mis eventos</a>
        </header>

        @if($errors->any())
            <div class="alert alert-danger"><strong>Revisa la información del evento.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('eventos.store') }}" enctype="multipart/form-data" class="workspace-form">
            @csrf
            <div class="workspace-form-grid">
                <section class="workspace-panel">
                    <div class="workspace-panel-heading"><span class="workspace-panel-icon">01</span><div><h2>Información del evento</h2><p>Nombre, fecha y categorías disponibles.</p></div></div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre del evento</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Ej. Copa AMC Motocross" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label event-date-label" for="event-date">Fecha del evento</label>
                            <input id="event-date" type="date" name="event_date" class="form-control event-date-field" value="{{ old('championship_start_date', old('event_date')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hora de inicio</label>
                            <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_championship" value="1" id="is_championship" @checked(old('is_championship'))>
                                <label class="form-check-label" for="is_championship">Crear un campeonato de varias fechas</label>
                            </div>
                        </div>
                        <div class="col-md-6 championship-field d-none">
                            <label class="form-label">Nombre del campeonato</label>
                            <input type="text" name="championship_name" class="form-control" value="{{ old('championship_name', old('title')) }}" placeholder="Ej. Campeonato AMC 2026">
                        </div>
                        <div class="col-md-3 championship-field d-none">
                            <label class="form-label">Número de fechas</label>
                            <select name="rounds" class="form-select">
                                @for($i = 2; $i <= 12; $i++)
                                    <option value="{{ $i }}" @selected(old('rounds', 6) == $i)>{{ $i }}</option>
                                @endfor
                            </select>
                            <small class="text-muted">Se creará un evento para cada fecha; puedes elegir 6 o 7.</small>
                        </div>
                        <div class="col-md-6 championship-field d-none">
                            <label class="form-label">Intervalo entre fechas</label>
                            <select name="championship_interval_days" class="form-select">
                                <option value="7" @selected(old('championship_interval_days', 7) == 7)>7 días</option>
                                <option value="14" @selected(old('championship_interval_days') == 14)>14 días</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Categorías</label>
                            <div class="workspace-category-list">
                                @foreach($categories as $category)
                                    <label class="workspace-category-option" for="category-{{ $category->id }}">
                                        <input class="form-check-input event-category-checkbox" type="checkbox" name="category_ids[]" value="{{ $category->id }}" id="category-{{ $category->id }}" @checked(in_array($category->id, old('category_ids', [])))>
                                        <span>{{ $category->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <small class="text-muted">Selecciona las categorías que competirán.</small>
                        </div>
                        @include('eventos.partials.category-fees', ['event' => null, 'categories' => $categories])
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" rows="5" class="form-control" placeholder="Reglas, observaciones e información importante">{{ old('description') }}</textarea>
                        </div>
                        @include('eventos.partials.circuit-picker', ['circuits' => $circuits, 'event' => null])
                    </div>
                </section>

                <section class="workspace-panel">
                    <div class="workspace-panel-heading"><span class="workspace-panel-icon">02</span><div><h2>Ubicación del circuito</h2><p>Indica el lugar y marca el punto exacto.</p></div></div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Dirección o nombre del circuito</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="Ej. Circuito El Choro, Cochabamba" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="organizer_whatsapp">WhatsApp de contacto</label>
                            <input type="tel" id="organizer_whatsapp" name="organizer_whatsapp" class="form-control" value="{{ old('organizer_whatsapp') }}" placeholder="+591 70000000" inputmode="tel" autocomplete="tel">
                            <small class="text-muted">Incluye el código de país. Aparecerá un botón para abrir el chat desde la ficha del evento.</small>
                        </div>
                        <div class="col-12">
                            @include('eventos.partials.map-picker', ['latitude' => null, 'longitude' => null, 'columnClass' => 'col-12'])
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cupo de pilotos</label>
                            <input type="number" name="max_participants" class="form-control" min="1" value="{{ old('max_participants') }}" placeholder="Sin límite">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cierre de inscripciones</label>
                            <input type="date" name="registration_deadline" class="form-control" value="{{ old('registration_deadline') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select" required>
                                @foreach(['programado' => 'Programado', 'en_curso' => 'En curso', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'programado') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Imagen de portada</label>
                            <input type="file" name="event_background" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <small class="text-muted">JPG, PNG o WEBP, máximo 4 MB.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Código QR para el pago</label>
                            <input type="file" name="payment_qr" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <small class="text-muted">JPG, PNG o WEBP, máximo 4 MB.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Instrucciones de pago</label>
                            <textarea name="payment_instructions" rows="3" class="form-control" placeholder="Banco, titular o referencia de pago">{{ old('payment_instructions') }}</textarea>
                        </div>
                    </div>
                </section>
            </div>

            <section id="championship-awards-panel" class="workspace-panel workspace-panel-wide championship-field d-none">
                @include('eventos.partials.championship-awards', ['categories' => $categories])
            </section>

            <div id="single-event-prizes-panel" class="workspace-panel workspace-panel-wide single-event-prizes-panel">
                <div class="workspace-panel-heading"><span class="workspace-panel-icon">03</span><div><h2>Premios y trofeos</h2><p>Asigna premios a cada categoría y puesto.</p></div></div>
                <div class="row g-3">
                    @include('eventos.partials.extras', ['event' => null, 'categories' => $categories, 'showStartingOrder' => false])
                </div>
            </div>

            <footer class="workspace-form-actions">
                <a href="{{ route('eventos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary" id="publish-event-button">Publicar evento</button>
            </footer>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const championshipToggle = document.getElementById('is_championship');
            const championshipFields = document.querySelectorAll('.championship-field');
            const eventDateField = document.querySelector('.event-date-field');
            const eventDateLabel = document.querySelector('.event-date-label');
            const publishButton = document.getElementById('publish-event-button');
            const championshipRounds = document.querySelector('select[name="rounds"]');
            const awardCategories = document.querySelectorAll('.workspace-championship-category');
            const awardEmptyState = document.getElementById('championship-awards-empty');

            const updateChampionshipMatrix = () => {
                const rounds = Number(championshipRounds.value);
                document.getElementById('championship-round-count').textContent = rounds;

                document.querySelectorAll('[data-round]').forEach((element) => {
                    const round = Number(element.dataset.round);
                    element.classList.toggle('d-none', round > rounds);
                    element.classList.toggle('is-final-round', round === rounds);

                    const label = element.querySelector('small, span:not(.workspace-award-trophy)');
                    if (label && element.closest('.workspace-round-legend-item')) {
                        label.textContent = round === rounds ? 'Puntos + premio final' : 'Puntos + trofeo';
                        element.classList.toggle('is-final', round === rounds);
                    } else if (label && element.matches('th')) {
                        label.textContent = round === rounds ? 'Puntos + premio' : 'Puntos + trofeo';
                    }
                });

                awardCategories.forEach((category) => {
                    category.querySelectorAll('[data-position]').forEach((slot) => {
                        const position = slot.dataset.position;
                        const prize = category.querySelector(`#championship-prize-${category.dataset.categoryId}-${position}`);
                        const finalSlot = category.querySelector(`#championship-prize-slot-${category.dataset.categoryId}-${position}-${rounds}`);
                        if (prize && finalSlot) {
                            finalSlot.append(prize.closest('.workspace-final-prize-control'));
                        }
                    });
                });
            };

            const updateSelectedAwardCategories = () => {
                const selectedIds = [...document.querySelectorAll('.event-category-checkbox:checked')]
                    .map((checkbox) => String(checkbox.value));
                let visibleCount = 0;
                awardCategories.forEach((category) => {
                    const selected = selectedIds.includes(String(category.dataset.categoryId));
                    category.classList.toggle('d-none', !selected);
                    visibleCount += selected ? 1 : 0;
                });
                awardEmptyState?.classList.toggle('d-none', visibleCount > 0);
            };

            const updateChampionshipFields = () => {
                const shouldShow = championshipToggle.checked;
                championshipFields.forEach((field) => {
                    field.classList.toggle('d-none', !shouldShow);
                });
                document.getElementById('single-event-prizes-panel').classList.toggle('d-none', shouldShow);
                document.querySelectorAll('#single-event-prizes-panel input, #single-event-prizes-panel select, #single-event-prizes-panel textarea')
                    .forEach((field) => { field.disabled = shouldShow; });
                document.querySelectorAll('#championship-awards-panel input')
                    .forEach((field) => { field.disabled = !shouldShow; });

                if (shouldShow) {
                    eventDateField.name = 'championship_start_date';
                    eventDateField.required = true;
                    eventDateLabel.textContent = 'Fecha de la primera fecha';
                    publishButton.textContent = 'Crear campeonato';
                } else {
                    eventDateField.name = 'event_date';
                    eventDateField.required = true;
                    eventDateLabel.textContent = 'Fecha del evento';
                    publishButton.textContent = 'Publicar evento';
                }
            };

            championshipToggle.addEventListener('change', updateChampionshipFields);
            championshipRounds.addEventListener('change', updateChampionshipMatrix);
            document.querySelectorAll('.event-category-checkbox').forEach((checkbox) => {
                checkbox.addEventListener('change', updateSelectedAwardCategories);
            });
            updateChampionshipMatrix();
            updateSelectedAwardCategories();
            updateChampionshipFields();
        });
    </script>
</x-app-layout>
