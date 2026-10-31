<x-app-layout>
    <div class="workspace-page">
        <header class="workspace-page-heading">
            <div><span class="workspace-eyebrow">Biblioteca personal</span><h1>Circuitos</h1><p>Guarda fotos para reutilizarlas como portada al crear tus eventos.</p></div>
        </header>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <section class="workspace-panel">
            <div class="workspace-panel-heading"><span class="workspace-panel-icon">＋</span><div><h2>Agregar circuito</h2><p>Sube una foto y asígnale un nombre para encontrarla al crear eventos.</p></div></div>
            <form method="POST" action="{{ route('organizador.circuitos.store') }}" enctype="multipart/form-data" class="workspace-circuit-upload">
                @csrf
                <div><label for="circuit-name" class="form-label">Nombre del circuito</label><input id="circuit-name" name="name" type="text" class="form-control" value="{{ old('name') }}" maxlength="120" placeholder="Ej. Circuito Quintanilla" required></div>
                <div><label for="circuit-image" class="form-label">Foto del circuito</label><input id="circuit-image" name="image" type="file" class="form-control" accept="image/jpeg,image/png,image/webp" required><small class="text-muted">JPG, PNG o WEBP, máximo 5 MB.</small></div>
                <button type="submit" class="btn btn-primary">Guardar circuito</button>
            </form>
        </section>

        <section class="workspace-panel workspace-panel-wide">
            <div class="workspace-panel-heading"><span class="workspace-panel-icon">▧</span><div><h2>Mis circuitos</h2><p>{{ $circuits->count() }} {{ $circuits->count() === 1 ? 'foto guardada' : 'fotos guardadas' }}</p></div></div>
            @if($circuits->isEmpty())
                <div class="organizer-events-empty"><span aria-hidden="true">▧</span><div><strong>Aún no tienes circuitos</strong><p>Agrega una foto arriba; después podrás seleccionarla como portada de tus eventos.</p></div></div>
            @else
                <div class="workspace-circuit-library">
                    @foreach($circuits as $circuit)
                        <article class="workspace-circuit-library-card">
                            <img src="{{ url('/storage/'.$circuit->image_path) }}" alt="Foto de {{ $circuit->name }}">
                            <div class="workspace-circuit-library-info">
                                <div><strong>{{ $circuit->name }}</strong><small>{{ $circuit->events_count }} {{ $circuit->events_count === 1 ? 'evento asociado' : 'eventos asociados' }}</small></div>
                                @if($circuit->events_count === 0)
                                    <form method="POST" action="{{ route('organizador.circuitos.destroy', $circuit) }}" onsubmit="return confirm('¿Eliminar esta foto de circuito?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Eliminar {{ $circuit->name }}">Eliminar</button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>