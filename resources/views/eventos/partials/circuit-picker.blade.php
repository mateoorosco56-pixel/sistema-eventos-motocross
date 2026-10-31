@php($selectedCircuitId = old('circuit_id', $event?->circuit_id))

<div class="col-12">
    <fieldset class="m-0">
        <legend class="form-label">Foto de mis circuitos</legend>
        <p class="small text-muted mb-2">Selecciona la portada del evento. Si no eliges una, se usará la imagen propia del evento.</p>
        <div class="workspace-circuit-gallery">
            <label class="workspace-circuit-card">
                <input type="radio" name="circuit_id" value="" @checked(blank($selectedCircuitId))>
                <span class="workspace-circuit-placeholder">Sin circuito</span>
                <span>Usar portada propia</span>
            </label>
            @foreach($circuits as $circuit)
                <label class="workspace-circuit-card">
                    <input type="radio" name="circuit_id" value="{{ $circuit->id }}" @checked((string) $selectedCircuitId === (string) $circuit->id)>
                    <img src="{{ url('/storage/'.$circuit->image_path) }}" alt="Foto del circuito {{ $circuit->name }}">
                    <span>{{ $circuit->name }}</span>
                </label>
            @endforeach
        </div>
        @if($circuits->isEmpty())
            <a class="btn btn-sm btn-outline-primary mt-2" href="{{ route('organizador.circuitos.index') }}">Agregar fotos a Circuitos</a>
        @endif
        @error('circuit_id')<small class="d-block text-danger mt-2">{{ $message }}</small>@enderror
    </fieldset>
</div>