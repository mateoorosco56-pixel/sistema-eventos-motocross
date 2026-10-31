@php
    $selectedCategoryIds = old('category_ids', $event?->categories?->pluck('id')->all() ?? []);
@endphp

<div class="col-12">
    <label class="form-label">Categorías disponibles y costo de inscripción</label>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">Categoría</th>
                    <th scope="col" style="width: 220px">Costo (Bs.)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    @php($isSelected = in_array($category->id, $selectedCategoryIds))
                    <tr data-category-fee-row="{{ $category->id }}" @class(['d-none' => !$isSelected])>
                        <td>{{ $category->name }}</td>
                        <td>
                            <input type="number" name="category_fees[{{ $category->id }}]" class="form-control form-control-sm" min="0" step="0.01" value="{{ old('category_fees.'.$category->id, $category->pivot->entry_fee ?? $event?->entry_fee) }}" aria-label="Costo de inscripción para {{ $category->name }}" @disabled(!$isSelected) @required($isSelected)>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const syncCategoryFeeRows = function () {
                    document.querySelectorAll('.event-category-checkbox').forEach(function (checkbox) {
                        const row = document.querySelector(`[data-category-fee-row="${checkbox.value}"]`);
                        const feeInput = row?.querySelector('input[name^="category_fees["]');
                        if (!row || !feeInput) return;

                        row.classList.toggle('d-none', !checkbox.checked);
                        feeInput.disabled = !checkbox.checked;
                        feeInput.required = checkbox.checked;
                    });
                };

                document.querySelectorAll('.event-category-checkbox').forEach(function (checkbox) {
                    checkbox.addEventListener('change', syncCategoryFeeRows);
                });
                syncCategoryFeeRows();
            });
        </script>
    @endpush
@endonce