<x-app-layout>
    <div class="container py-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Editar inscripción</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('inscripciones.update', $registration) }}">
                    @csrf
                    @method('PATCH')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Evento</label>
                            <input type="text" class="form-control" value="{{ $registration->event->title ?? 'Evento' }}" disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nombre completo</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $registration->participant->name ?? '') }}" {{ auth()->user()->role === 'piloto' ? '' : 'disabled' }} required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Documento</label>
                            <input type="text" name="document" class="form-control" value="{{ old('document', $registration->participant->document ?? '') }}" {{ auth()->user()->role === 'piloto' ? '' : 'disabled' }} required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $registration->participant->email ?? '') }}" {{ auth()->user()->role === 'piloto' ? '' : 'disabled' }} required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $registration->participant->phone ?? '') }}" {{ auth()->user()->role === 'piloto' ? '' : 'disabled' }} required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Equipo</label>
                            <input type="text" name="team" class="form-control" value="{{ old('team', $registration->participant->team ?? '') }}" {{ auth()->user()->role === 'piloto' ? '' : 'disabled' }}>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Categoría</label>
                            @if(auth()->user()->role === 'piloto')
                                @if($categories->isEmpty())
                                    <div class="alert alert-warning mb-0">Este evento no tiene categorías disponibles.</div>
                                @else
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Selecciona una categoría</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" @selected((string) old('category_id', $registration->category_id) === (string) $category->id)>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            @else
                                <input type="text" class="form-control" value="{{ $registration->category->name ?? '-' }}" disabled>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Dorsal</label>
                            <input type="number" name="dorsal" class="form-control @error('dorsal') is-invalid @enderror" min="1" value="{{ old('dorsal', $registration->dorsal ?? '') }}" {{ in_array(auth()->user()->role, ['piloto', 'organizador'], true) ? '' : 'disabled' }} required>
                            @error('dorsal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if(auth()->user()->role !== 'piloto')
                        <div class="col-md-6">
                            <label class="form-label">Estado del pago</label>
                            <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $registration->payment_status ?? 'pendiente')) }}" disabled>
                            @if($registration->payment_proof_path)
                                <a href="{{ route('inscripciones.payment-proof', $registration) }}" class="btn btn-sm btn-outline-primary mt-2" target="_blank" rel="noopener" download>Descargar comprobante</a>
                            @else
                                <small class="text-muted">El piloto aún no ha enviado comprobante.</small>
                            @endif
                        </div>
                        @if($registration->payment_proof_path)
                            <div class="col-12">
                                <div class="border rounded p-3 bg-light">
                                    <strong class="d-block mb-3">Comprobante enviado</strong>
                                    @php($proofExtension = strtolower(pathinfo($registration->payment_proof_path, PATHINFO_EXTENSION)))
                                    @if(in_array($proofExtension, ['jpg', 'jpeg', 'png', 'webp']))
                                        <p class="mb-2 text-muted">Ver comprobante</p>
                                        <a href="{{ route('inscripciones.payment-proof', $registration) }}" target="_blank" rel="noopener">
                                            <img src="{{ route('inscripciones.payment-proof', $registration) }}" alt="Comprobante de pago" class="img-fluid rounded border" style="max-height: 360px;">
                                        </a>
                                    @else
                                        <p class="mb-0 text-muted">Este comprobante es un PDF. Puedes abrirlo para revisarlo.</p>
                                    @endif
                                    <div class="mt-3">
                                        <a href="{{ route('inscripciones.payment-proof', $registration) }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" download>Descargar comprobante</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <form action="{{ route('inscripciones.validate-payment', $registration) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="payment_status" value="aprobado">
                                        <input type="hidden" name="status" value="confirmada">
                                        <button type="submit" class="btn btn-success">Aceptar pago e inscripción</button>
                                    </form>
                                    <form action="{{ route('inscripciones.validate-payment', $registration) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="payment_status" value="rechazado">
                                        <input type="hidden" name="status" value="rechazada">
                                        <input type="text" name="cancellation_reason" class="form-control form-control-sm mb-2" maxlength="1000" placeholder="Motivo del rechazo (opcional)">
                                        <button type="submit" class="btn btn-outline-danger">Rechazar pago e inscripción</button>
                                    </form>
                                </div>
                            </div>
                        @endif
                        @else
                            <input type="hidden" name="status" value="{{ $registration->status }}">
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
