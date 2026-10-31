@if($event->cover_path)
    <a href="{{ route('eventos.show', $event) }}" class="d-block overflow-hidden" aria-label="Ver detalles de {{ $event->title }}">
        <img src="{{ url('/storage/'.$event->cover_path) }}" alt="Portada del evento {{ $event->title }}" class="w-100" style="height: 210px; object-fit: cover;">
    </a>
@endif