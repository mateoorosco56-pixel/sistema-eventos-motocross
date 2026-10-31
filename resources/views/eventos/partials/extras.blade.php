@php
    $event = $event ?? null;
    $showStartingOrder = $showStartingOrder ?? true;
    $collapsiblePrizes = $collapsiblePrizes ?? false;
    $availableCategories = $event?->categories ?? collect();
    if ($event?->category && ! $availableCategories->contains('id', $event->category->id)) {
        $availableCategories = $availableCategories->push($event->category);
    }
    if (! $event) {
        $availableCategories = $categories;
    }
    $prizeRows = old('prizes');
    if ($prizeRows === null) {
        $prizeRows = $event?->prizes?->map(fn ($prize) => [
            'category_id' => $prize->category_id,
            'position' => $prize->position,
            'award_type' => $prize->award_type,
            'description' => $prize->description,
            'amount' => $prize->amount,
        ])->values()->all() ?? [];
    }

    $startingOrderRows = old('starting_orders');
    if ($startingOrderRows === null) {
        $startingOrderRows = $event?->startingOrders?->map(fn ($startingOrder) => [
            'category_id' => $startingOrder->category_id,
            'position' => $startingOrder->position,
            'scheduled_time' => $startingOrder->scheduled_time ? substr($startingOrder->scheduled_time, 0, 5) : null,
            'is_published' => $startingOrder->is_published,
        ])->values()->all() ?? [];
    }
    $publishStartingOrder = old('publish_starting_order', collect($startingOrderRows)->contains(fn ($row) => filter_var($row['is_published'] ?? false, FILTER_VALIDATE_BOOLEAN)));
@endphp

<div class="col-12">
    @if($collapsiblePrizes)
    <details class="workspace-collapsible-prizes" @if(old('prizes') !== null || $errors->has('prizes')) open @endif>
        <summary>
            <span>
                <strong>Premios y trofeos por categoría</strong>
                <small>Configura el premio de cada puesto</small>
            </span>
            <span class="workspace-collapsible-prizes-count">{{ count($prizeRows) }} configurados</span>
        </summary>
    @endif
    <div class="card border bg-light-subtle">
        <div class="card-body">
            @unless($collapsiblePrizes)
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="fw-bold mb-1">Premios y trofeos por categoría</h5>
                    <p class="small text-muted mb-0">Relaciona cada premio con una categoría y un puesto.</p>
                </div>
                <span class="badge bg-dark">3 premios por categoría</span>
            </div>
            @endunless
            <div id="prizes-container" class="d-grid gap-2 workspace-prizes-list">
                @foreach ($prizeRows as $index => $prize)
                    @include('eventos.partials.prize-row', ['index' => $index, 'prize' => $prize, 'availableCategories' => $availableCategories])
                @endforeach
            </div>
            <div id="prizes-empty" class="small text-muted {{ count($prizeRows) ? 'd-none' : '' }}">Todavía no hay premios registrados.</div>
        </div>
    </div>
    @if($collapsiblePrizes)
    </details>
    @endif
</div>

@if($showStartingOrder)
<div class="col-12">
    <div class="card border bg-light-subtle">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="fw-bold mb-1">Orden de largada</h5>
                    <p class="small text-muted mb-0">Agrega las categorías en el orden de largada. El orden completo se publica de una sola vez.</p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-dark" id="add-starting-order-row">+ Agregar categoría</button>
            </div>
            <div id="starting-orders-container" class="d-grid gap-3">
                @foreach ($startingOrderRows as $index => $startingOrder)
                    @include('eventos.partials.starting-order-row', ['index' => $index, 'startingOrder' => $startingOrder, 'availableCategories' => $availableCategories])
                @endforeach
            </div>
            <div id="starting-orders-empty" class="small text-muted {{ count($startingOrderRows) ? 'd-none' : '' }}">Todavía no hay categorías en el orden de largada.</div>
            <div class="form-check mt-3">
                <input type="hidden" name="publish_starting_order" value="0">
                <input type="checkbox" name="publish_starting_order" value="1" class="form-check-input" id="publish-starting-order" @checked(filter_var($publishStartingOrder, FILTER_VALIDATE_BOOLEAN))>
                <label class="form-check-label fw-semibold" for="publish-starting-order">Publicar orden completo para el público</label>
            </div>
        </div>
    </div>
</div>
@endif

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const categories = @json($availableCategories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])->values());
                const prizesContainer = document.getElementById('prizes-container');
                const startingOrdersContainer = document.getElementById('starting-orders-container');
                const prizesEmpty = document.getElementById('prizes-empty');
                const startingOrdersEmpty = document.getElementById('starting-orders-empty');

                const escapeHtml = function (value) {
                    return String(value).replace(/[&<>'"]/g, character => ({
                        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
                    }[character]));
                };

                const refreshEmptyState = function (container, emptyState) {
                    emptyState.classList.toggle('d-none', container.children.length > 0);
                };

                const syncEventCategories = function () {
                    const selectedIds = [...document.querySelectorAll('.event-category-checkbox:checked')]
                        .map(input => String(input.value));

                    document.querySelectorAll('#prizes-container select[name$="[category_id]"] , #starting-orders-container select[name$="[category_id]"]')
                        .forEach(select => {
                            const currentValue = String(select.value);
                            select.innerHTML = '<option value="">Selecciona</option>' + categories
                                .filter(category => selectedIds.includes(String(category.id)))
                                .map(category => `<option value="${category.id}">${category.name}</option>`)
                                .join('');
                            select.value = selectedIds.includes(currentValue) ? currentValue : '';
                        });
                };

                @if($showStartingOrder)
                const preparePrizeRows = function () {
                    const values = {};
                    prizesContainer.querySelectorAll('.prize-row').forEach(row => {
                        const categoryId = row.querySelector('select[name$="[category_id]"]')?.value;
                        const position = row.querySelector('select[name$="[position]"]')?.value;
                        if (!categoryId || !position) {
                            return;
                        }

                        values[`${categoryId}-${position}`] = {
                            awardType: row.querySelector('input[name$="[award_type]"]')?.value ?? '',
                            amount: row.querySelector('input[name$="[amount]"]')?.value ?? '',
                            description: row.querySelector('input[name$="[description]"]')?.value ?? '',
                        };
                    });

                    const selectedIds = [...document.querySelectorAll('.event-category-checkbox:checked')]
                        .map(input => String(input.value));
                    let index = 0;
                    prizesContainer.innerHTML = '';

                    categories
                        .filter(category => selectedIds.includes(String(category.id)))
                        .forEach(category => {
                            [1, 2, 3].forEach(position => {
                                const saved = values[`${category.id}-${position}`] ?? {};
                                prizesContainer.insertAdjacentHTML('beforeend', `
                                    <div class="row g-2 align-items-end workspace-prize-row prize-row">
                                        <div class="col-md-3"><label class="form-label small mb-1">Categoría</label><select name="prizes[${index}][category_id]" class="form-select form-select-sm" required><option value="${category.id}">${escapeHtml(category.name)}</option></select></div>
                                        <div class="col-md-2"><label class="form-label small mb-1">Puesto</label><select name="prizes[${index}][position]" class="form-select form-select-sm" required><option value="${position}" selected>${position}.º lugar</option></select></div>
                                        <div class="col-md-3"><label class="form-label small mb-1">Premio</label><input type="text" name="prizes[${index}][award_type]" class="form-control form-control-sm" value="${escapeHtml(saved.awardType ?? '')}" placeholder="Trofeo" required></div>
                                        <div class="col-md-2"><label class="form-label small mb-1">Monto (Bs.)</label><input type="number" name="prizes[${index}][amount]" class="form-control form-control-sm" min="0" step="0.01" value="${escapeHtml(saved.amount ?? '')}"></div>
                                        <div class="col-md-2"><label class="form-label small mb-1">Descripción</label><input type="text" name="prizes[${index}][description]" class="form-control form-control-sm" value="${escapeHtml(saved.description ?? '')}" placeholder="Opcional"></div>
                                    </div>`);
                                index++;
                            });
                        });

                    refreshEmptyState(prizesContainer, prizesEmpty);
                };

                const renumberStartingOrders = function () {
                    [...startingOrdersContainer.children].forEach((row, index) => {
                        const position = row.querySelector('.starting-order-position');
                        if (position) {
                            position.value = index + 1;
                        }
                    });
                };

                document.getElementById('add-starting-order-row')?.addEventListener('click', function () {
                    const index = Date.now();
                    startingOrdersContainer.insertAdjacentHTML('beforeend', `
                        <div class="row g-2 align-items-end border rounded p-3 starting-order-row">
                            <div class="col-md-4"><label class="form-label">Categoría</label><select name="starting_orders[${index}][category_id]" class="form-select" required><option value="">Selecciona</option>${categoryOptions()}</select></div>
                            <div class="col-md-2"><label class="form-label">Orden</label><input type="number" name="starting_orders[${index}][position]" class="form-control starting-order-position" min="1" max="100" readonly required></div>
                            <div class="col-md-3"><label class="form-label">Hora</label><input type="time" name="starting_orders[${index}][scheduled_time]" class="form-control"></div>
                            <div class="col-md-3 d-flex gap-1"><button type="button" class="btn btn-outline-secondary move-starting-order" data-direction="up" title="Subir">↑</button><button type="button" class="btn btn-outline-secondary move-starting-order" data-direction="down" title="Bajar">↓</button><button type="button" class="btn btn-outline-danger flex-grow-1 remove-extra-row" title="Quitar">Quitar</button></div>
                        </div>`);
                    renumberStartingOrders();
                    refreshEmptyState(startingOrdersContainer, startingOrdersEmpty);
                    syncEventCategories();
                });

                document.addEventListener('click', function (event) {
                    if (event.target.classList.contains('move-starting-order')) {
                        const row = event.target.closest('.starting-order-row');
                        const sibling = event.target.dataset.direction === 'up' ? row.previousElementSibling : row.nextElementSibling;
                        if (sibling) {
                            event.target.dataset.direction === 'up'
                                ? startingOrdersContainer.insertBefore(row, sibling)
                                : startingOrdersContainer.insertBefore(sibling, row);
                            renumberStartingOrders();
                        }
                        return;
                    }

                    if (!event.target.classList.contains('remove-extra-row')) {
                        return;
                    }

                    const row = event.target.closest('.prize-row, .starting-order-row');
                    const container = row.parentElement;
                    row.remove();
                    if (container === startingOrdersContainer) {
                        renumberStartingOrders();
                    }
                    refreshEmptyState(container, container === prizesContainer ? prizesEmpty : startingOrdersEmpty);
                });

                document.querySelectorAll('.event-category-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', function () {
                        preparePrizeRows();
                        syncEventCategories();
                    });
                });

                renumberStartingOrders();
                preparePrizeRows();
                syncEventCategories();
                @else
                const preparePrizeRows = function () {
                    const selectedIds = [...document.querySelectorAll('.event-category-checkbox:checked')]
                        .map(input => String(input.value));

                    let index = 0;
                    prizesContainer.innerHTML = '';
                    categories
                        .filter(category => selectedIds.includes(String(category.id)))
                        .forEach(category => {
                            [1, 2, 3].forEach(position => {
                                prizesContainer.insertAdjacentHTML('beforeend', `
                                    <div class="row g-2 align-items-end workspace-prize-row prize-row">
                                        <div class="col-md-3"><label class="form-label small mb-1">Categoría</label><select name="prizes[${index}][category_id]" class="form-select form-select-sm" required><option value="${category.id}">${escapeHtml(category.name)}</option></select></div>
                                        <div class="col-md-2"><label class="form-label small mb-1">Puesto</label><select name="prizes[${index}][position]" class="form-select form-select-sm" required><option value="${position}" selected>${position}.º lugar</option></select></div>
                                        <div class="col-md-3"><label class="form-label small mb-1">Premio</label><input type="text" name="prizes[${index}][award_type]" class="form-control form-control-sm" placeholder="Trofeo" required></div>
                                        <div class="col-md-2"><label class="form-label small mb-1">Monto (Bs.)</label><input type="number" name="prizes[${index}][amount]" class="form-control form-control-sm" min="0" step="0.01"></div>
                                        <div class="col-md-2"><label class="form-label small mb-1">Descripción</label><input type="text" name="prizes[${index}][description]" class="form-control form-control-sm" placeholder="Opcional"></div>
                                    </div>`);
                                index++;
                            });
                        });
                    refreshEmptyState(prizesContainer, prizesEmpty);
                };

                document.querySelectorAll('.event-category-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', preparePrizeRows);
                });

                preparePrizeRows();
                @endif
            });
        </script>
    @endpush
@endonce
