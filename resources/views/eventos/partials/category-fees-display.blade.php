<div class="pilot-event-card-fees small mb-3">
    <strong>Costo por categoría:</strong>
    @forelse($event->categories as $category)
        @php($categoryFee = $category->pivot->entry_fee ?? $event->entry_fee)
        <span>{{ $category->name }}: {{ $categoryFee !== null ? 'Bs. '.number_format((float) $categoryFee, 2) : 'Por definir' }}</span>
    @empty
        @if($event->category)
            <span>{{ $event->category->name }}: {{ $event->entry_fee !== null ? 'Bs. '.number_format((float) $event->entry_fee, 2) : 'Por definir' }}</span>
        @else
            <span>Por definir</span>
        @endif
    @endforelse
</div>
