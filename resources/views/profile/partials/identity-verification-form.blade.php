@if(auth()->user()->role === 'piloto')
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Verificación de identidad
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Sube ambas caras de tu carnet y una fotografía actual de tu rostro. El OCR local leerá el carnet y un modelo local comparará ambos rostros. Si no hay coincidencia clara, tu solicitud quedará pendiente para revisión del administrador. No se realiza una prueba de vida.
            </p>
        </header>

        <div class="mt-5 space-y-4">
            <div>
                <p class="text-sm text-gray-700">Estado actual:</p>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                    @if(auth()->user()->verification_status === 'verified') bg-green-100 text-green-800
                    @elseif(auth()->user()->verification_status === 'rejected') bg-red-100 text-red-800
                    @else bg-yellow-100 text-yellow-800 @endif">
                    @switch(auth()->user()->verification_status)
                        @case('verified') Verificada @break
                        @case('rejected') Rechazada @break
                        @default Pendiente de revisión
                    @endswitch
                </span>
            </div>

            @if(auth()->user()->verification_result)
                <p class="text-sm text-gray-700">Resultado: {{ auth()->user()->verification_result }}</p>
            @endif
            @if(auth()->user()->verification_rejection_reason)
                <p class="text-sm text-red-700">Motivo de rechazo: {{ auth()->user()->verification_rejection_reason }}</p>
            @endif
        </div>

        <form method="post" action="{{ route('profile.verification') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf

            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700">Nombres</label>
                <input id="first_name" name="first_name" type="text" value="{{ old('first_name', auth()->user()->first_name) }}" maxlength="127" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @error('first_name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700">Apellidos</label>
                <input id="last_name" name="last_name" type="text" value="{{ old('last_name', auth()->user()->last_name) }}" maxlength="127" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @error('last_name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="identity_document_number" class="block text-sm font-medium text-gray-700">Número de carnet de identidad</label>
                <input id="identity_document_number" name="identity_document_number" type="text" value="{{ old('identity_document_number', auth()->user()->identity_document_number) }}" maxlength="30" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @error('identity_document_number') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="document_photo" class="block text-sm font-medium text-gray-700">Fotografía del anverso (frente) del carnet</label>
                <input id="document_photo" name="document_photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-600" required>
                @error('document_photo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="document_back_photo" class="block text-sm font-medium text-gray-700">Fotografía del reverso del carnet</label>
                <input id="document_back_photo" name="document_back_photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-600" required>
                @error('document_back_photo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="selfie_photo" class="block text-sm font-medium text-gray-700">Selfie actual</label>
                <input id="selfie_photo" name="selfie_photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-600" required>
                @error('selfie_photo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="identity_processing_consent" value="1" required>
                    <span>Autorizo el procesamiento local de ambas caras de mi carnet y fotografías para validar mi identidad. Si no es concluyente, un administrador revisará la solicitud. No se realiza prueba de vida.</span>
                </label>
                @error('identity_processing_consent') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Enviar verificación
                </button>
            </div>
        </form>
    </section>
@endif
