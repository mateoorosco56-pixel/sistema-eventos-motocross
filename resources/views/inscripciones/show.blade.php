<x-app-layout>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-primary mb-2">Mi inscripción</p>
                <h2 class="fw-bold mb-1">Detalle de inscripción</h2>
            </div>
            <a href="{{ route('inscripciones.index') }}" class="btn btn-outline-primary">Volver</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @if(session('payment_submitted'))
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
                        <span class="fs-5" aria-hidden="true">✓</span>
                        <div><strong>Comprobante enviado correctamente.</strong><br><span>{{ session('success') }}</span></div>
                    </div>
                @endif

                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <span class="badge bg-{{ $registration->status === 'confirmada' ? 'success' : ($registration->status === 'rechazada' ? 'danger' : 'warning text-dark') }} mb-2">
                            {{ ucfirst($registration->status) }}
                        </span>
                        <h3 class="fw-bold mb-1">{{ $registration->event->title }}</h3>
                    </div>
                    @if($registration->status === 'pendiente')
                        <form action="{{ route('piloto.inscripciones.cancel', $registration) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <input type="text" name="cancellation_reason" class="form-control form-control-sm mb-2" maxlength="1000" placeholder="Motivo de cancelación (opcional)">
                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('¿Deseas cancelar esta inscripción?')">Cancelar inscripción</button>
                        </form>
                    @endif
                    @if($registration->status === 'rechazada' || $registration->status === 'cancelada')
                        <div class="alert alert-warning mb-0">
                            <strong>Motivo:</strong> {{ $registration->cancellation_reason ?: 'No especificado.' }}
                            @if(config('services.whatsapp.number'))
                                <a class="btn btn-sm btn-success ms-2" target="_blank" rel="noopener" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('services.whatsapp.number')) }}?text={{ urlencode('Consulta sobre mi inscripción al evento '.$registration->event->title) }}">Contactar por WhatsApp</a>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6"><div class="bg-light rounded p-3 h-100"><small class="text-muted d-block">Fecha</small><strong>{{ \Carbon\Carbon::parse($registration->event->event_date)->format('d/m/Y') }}</strong></div></div>
                    <div class="col-md-6"><div class="bg-light rounded p-3 h-100"><small class="text-muted d-block">Ubicación</small><strong>{{ $registration->event->location }}</strong></div></div>
                    <div class="col-md-6"><div class="bg-light rounded p-3 h-100"><small class="text-muted d-block">Categoría</small><strong>{{ $registration->category->name ?? 'No especificada' }}</strong></div></div>
                    <div class="col-md-6"><div class="bg-light rounded p-3 h-100"><small class="text-muted d-block">Costo de inscripción</small><strong>{{ $registration->event->entry_fee !== null ? '$'.number_format($registration->event->entry_fee, 2) : 'No especificado' }}</strong></div></div>
                </div>

                @if($registration->event->entry_fee !== null || $registration->event->payment_qr_path || $registration->event->payment_instructions)
                    <div id="pago-inscripcion" class="border rounded p-3 mb-4">
                        <h5 class="fw-bold mb-3">Pago de inscripción</h5>
                        @if($registration->event->payment_qr_path)
                            <img src="{{ url('/storage/'.$registration->event->payment_qr_path) }}" alt="Código QR para pagar la inscripción" class="img-fluid border rounded mb-3" style="max-width: 260px;">
                        @endif
                        @if($registration->event->payment_instructions)
                            <p class="mb-3">{{ $registration->event->payment_instructions }}</p>
                        @endif
                        <p class="mb-3">Estado del pago: <strong>{{ ucfirst(str_replace('_', ' ', $registration->payment_status ?? 'pendiente')) }}</strong></p>

                        @if($registration->payment_proof_path)
                            @php($proofExtension = strtolower(pathinfo($registration->payment_proof_path, PATHINFO_EXTENSION)))
                            <div class="bg-light border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                    <strong>Comprobante enviado</strong>
                                    <small class="text-muted">{{ optional($registration->payment_submitted_at)->format('d/m/Y H:i') }}</small>
                                </div>
                                @if(in_array($proofExtension, ['jpg', 'jpeg', 'png', 'webp']))
                                    <a href="{{ route('piloto.inscripciones.payment-proof', $registration) }}" target="_blank" rel="noopener">
                                        <img src="{{ route('piloto.inscripciones.payment-proof', $registration) }}" alt="Comprobante de pago enviado" class="img-fluid rounded border" style="max-height: 360px;">
                                    </a>
                                @else
                                    <a href="{{ route('piloto.inscripciones.payment-proof', $registration) }}" target="_blank" rel="noopener" class="btn btn-outline-primary">Ver comprobante PDF</a>
                                @endif
                            </div>
                            <p class="text-muted mb-3">Puedes reemplazarlo enviando otro archivo.</p>
                        @endif

                        @if($registration->payment_status !== 'aprobado' && $registration->status !== 'rechazada')
                            <form action="{{ route('piloto.inscripciones.payment', $registration) }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-md-8">
                                    <label class="form-label">Comprobante de pago</label>
                                    <input type="file" name="payment_proof" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                                    <small class="text-muted">JPG, PNG, WEBP o PDF, máximo 5 MB.</small>
                                </div>
                                <div class="col-md-4"><button type="submit" class="btn btn-primary w-100">Enviar comprobante</button></div>
                            </form>
                        @endif
                    </div>
                @endif

                @if($registration->event->latitude !== null && $registration->event->longitude !== null)
                    <div class="mb-4">
                        <h5 class="fw-bold mb-2">Ubicación del circuito</h5>
                        @php($event = $registration->event)
                        @include('eventos.partials.map-viewer')
                    </div>
                @endif

                <h5 class="fw-bold mb-3">Datos del piloto</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><small class="text-muted d-block">Nombre</small><strong>{{ $registration->participant->name ?? '-' }}</strong></div>
                    <div class="col-md-6"><small class="text-muted d-block">Documento</small><strong>{{ $registration->participant->document ?? '-' }}</strong></div>
                    <div class="col-md-6"><small class="text-muted d-block">Correo</small><strong>{{ $registration->participant->email ?? '-' }}</strong></div>
                    <div class="col-md-6"><small class="text-muted d-block">Teléfono</small><strong>{{ $registration->participant->phone ?? '-' }}</strong></div>
                    <div class="col-md-6"><small class="text-muted d-block">Equipo</small><strong>{{ $registration->participant->team ?: 'Sin equipo' }}</strong></div>
                    <div class="col-md-6"><small class="text-muted d-block">Dorsal</small><strong>{{ $registration->dorsal ?? '-' }}</strong></div>
                </div>

                <h5 class="fw-bold mb-2">Información del evento</h5>
                <p class="text-muted mb-0">{{ $registration->event->description ?: 'Este evento no tiene información adicional.' }}</p>
            </div>
        </div>
    </div>
</x-app-layout>

@if(session('focus_payment'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('pago-inscripcion')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    </script>
@endif