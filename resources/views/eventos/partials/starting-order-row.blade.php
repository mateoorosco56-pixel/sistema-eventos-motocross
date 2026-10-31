<div class="row g-2 align-items-end border rounded p-3 starting-order-row">
    <div class="col-md-4">
        <label class="form-label">Categoría</label>
        <select name="starting_orders[{{ $index }}][category_id]" class="form-select" required>
            <option value="">Selecciona</option>
            @foreach ($availableCategories as $category)
                <option value="{{ $category->id }}" @selected((string) ($startingOrder['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Orden</label>
        <input type="number" name="starting_orders[{{ $index }}][position]" class="form-control starting-order-position" min="1" max="100" value="{{ $startingOrder['position'] ?? '' }}" readonly required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Hora</label>
        <input type="time" name="starting_orders[{{ $index }}][scheduled_time]" class="form-control" value="{{ $startingOrder['scheduled_time'] ?? '' }}">
    </div>
    <div class="col-md-3 d-flex gap-1">
        <button type="button" class="btn btn-outline-secondary move-starting-order" data-direction="up" title="Subir">↑</button>
        <button type="button" class="btn btn-outline-secondary move-starting-order" data-direction="down" title="Bajar">↓</button>
        <button type="button" class="btn btn-outline-danger flex-grow-1 remove-extra-row" title="Quitar">Quitar</button>
    </div>
</div>
