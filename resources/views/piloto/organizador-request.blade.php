<x-app-layout>
    <div class="container-fluid py-4">
        <h2 class="fw-bold mb-2">Solicitar rol de organizador</h2>
        <p class="text-muted">El administrador revisará tu solicitud.</p>

        @if($request?->status === 'rechazada')
            <div class="alert alert-danger">Tu solicitud fue rechazada. {{ $request->admin_note }}</div>
        @endif

        <form method="POST" action="{{ route('organizer-requests.store') }}" class="card border-0 shadow-sm p-4" style="max-width: 700px">
            @csrf
            <label for="reason" class="form-label">Motivo de la solicitud</label>
            <textarea id="reason" name="reason" class="form-control mb-3" rows="5">{{ old('reason', $request?->reason) }}</textarea>
            @error('reason') <div class="text-danger mb-3">{{ $message }}</div> @enderror
            <button class="btn btn-dark">Enviar solicitud</button>
        </form>
    </div>
</x-app-layout>