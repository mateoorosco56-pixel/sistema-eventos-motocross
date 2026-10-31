<x-app-layout>
    @php
        $categoryFee = $registration->event->categories->firstWhere('id', $registration->category_id)?->pivot->entry_fee
            ?? $registration->event->entry_fee;
    @endphp

    <main class="pilot-payment-page">
        <section class="pilot-payment-card" aria-label="Pago de inscripción">
            @if(session('payment_submitted'))
                <div class="pilot-payment-success" role="status">
                    <span aria-hidden="true">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <header class="pilot-payment-event">
                @if($registration->event->cover_path)
                    <img src="{{ url('/storage/'.$registration->event->cover_path) }}" alt="" class="pilot-payment-event-image">
                @else
                    <span class="pilot-payment-event-placeholder" aria-hidden="true">🏁</span>
                @endif
                <div class="pilot-payment-event-copy">
                    <h1>{{ $registration->event->title }}</h1>
                    <p>Categoría: {{ $registration->category->name ?? 'No especificada' }}</p>
                </div>
            </header>

            <div class="pilot-payment-amount">
                <span class="pilot-payment-amount-icon" aria-hidden="true">▣</span>
                <span><small>Monto de inscripción</small><strong>{{ $categoryFee !== null ? 'Bs. '.number_format((float) $categoryFee, 2, ',', '.') : 'Por definir' }}</strong></span>
            </div>

            <details class="pilot-payment-method">
                <summary>
                    <span class="pilot-payment-method-icon" aria-hidden="true">▣</span>
                    <span class="pilot-payment-method-copy"><strong>Método de pago</strong><small>Transferencia por QR</small><small>Escanea el código QR para realizar el pago.</small></span>
                    <span class="pilot-payment-method-chevron" aria-hidden="true">›</span>
                </summary>
                <div class="pilot-payment-method-details">
                    @if($registration->event->payment_qr_path)
                        <img src="{{ url('/storage/'.$registration->event->payment_qr_path) }}" alt="Código QR para pagar la inscripción">
                    @endif
                    @if($registration->event->payment_instructions)
                        <p>{{ $registration->event->payment_instructions }}</p>
                    @endif
                    @unless($registration->event->payment_qr_path || $registration->event->payment_instructions)
                        <p>Los datos de pago aún no están disponibles para este evento.</p>
                    @endunless
                </div>
            </details>

            @if($registration->payment_status === 'aprobado')
                <div class="pilot-payment-status is-approved" role="status">Pago aprobado</div>
            @elseif($registration->payment_proof_path)
                <div class="pilot-payment-status" role="status">Comprobante enviado. Puedes reemplazarlo si es necesario.</div>
            @endif

            @if($registration->payment_status !== 'aprobado' && $registration->status !== 'rechazada')
                <form action="{{ route('piloto.inscripciones.payment', $registration) }}" method="POST" enctype="multipart/form-data" class="pilot-payment-form">
                    @csrf
                    <label for="payment-proof" class="pilot-payment-upload">
                        <span class="pilot-payment-upload-icon" aria-hidden="true">☁</span>
                        <strong>Sube una imagen del comprobante</strong>
                        <small>JPG, PNG, WEBP o PDF (máx. 5 MB)</small>
                        <span class="pilot-payment-file-button">Seleccionar archivo</span>
                        <span id="payment-proof-name" class="pilot-payment-file-name">Ningún archivo seleccionado</span>
                    </label>
                    <input id="payment-proof" type="file" name="payment_proof" accept="image/jpeg,image/png,image/webp,application/pdf" required aria-label="Seleccionar imagen o PDF del comprobante">
                    @error('payment_proof')
                        <p class="pilot-payment-error">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="pilot-payment-submit">Enviar comprobante</button>
                </form>
            @endif
        </section>
    </main>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const input = document.getElementById('payment-proof');
                const fileName = document.getElementById('payment-proof-name');

                if (input && fileName) {
                    input.addEventListener('change', function () {
                        fileName.textContent = input.files.length ? input.files[0].name : 'Ningún archivo seleccionado';
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>