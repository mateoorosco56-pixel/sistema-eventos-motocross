<div class="row g-2 align-items-end workspace-prize-row prize-row">
    <div class="col-12 col-md-3">
        <label class="form-label small mb-1">Categoría</label>
        <select name="prizes[{{ $index }}][category_id]" class="form-select form-select-sm" required>
            <option value="">Selecciona</option>
            @foreach ($availableCategories as $category)
                <option value="{{ $category->id }}" @selected((string) ($prize['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-2">
        <label class="form-label small mb-1">Puesto</label>
        <select name="prizes[{{ $index }}][position]" class="form-select form-select-sm" required>
            <option value="">Selecciona</option>
            @foreach ([1 => '1.º lugar', 2 => '2.º lugar', 3 => '3.º lugar'] as $position => $label)
                <option value="{{ $position }}" @selected((string) ($prize['position'] ?? '') === (string) $position)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label small mb-1">Premio</label>
        <input type="text" name="prizes[{{ $index }}][award_type]" class="form-control form-control-sm" value="{{ $prize['award_type'] ?? '' }}" placeholder="Trofeo" required>
    </div>
    <div class="col-12 col-md-2">
        <label class="form-label small mb-1">Monto (Bs.)</label>
        <input type="number" name="prizes[{{ $index }}][amount]" class="form-control form-control-sm" min="0" step="0.01" value="{{ $prize['amount'] ?? '' }}">
    </div>
    <div class="col-12 col-md-2">
        <label class="form-label small mb-1">Descripción</label>
        <input type="text" name="prizes[{{ $index }}][description]" class="form-control form-control-sm" value="{{ $prize['description'] ?? '' }}" placeholder="Opcional">
    </div>
</div>
