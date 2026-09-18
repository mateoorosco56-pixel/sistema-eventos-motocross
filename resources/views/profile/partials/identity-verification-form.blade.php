@if(auth()->user()->role === 'piloto')
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Verificación de identidad
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Sube tu documento de identidad y una selfie actual para verificar tu cuenta como piloto.
            </p>
        </header>

        <div class="mt-5 space-y-4">
            <div>
                <p class="text-sm text-gray-700">Estado actual:</p>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                    @if(auth()->user()->verification_status === 'verificada') bg-green-100 text-green-800
                    @elseif(auth()->user()->verification_status === 'rechazada') bg-red-100 text-red-800
                    @else bg-yellow-100 text-yellow-800 @endif">
                    {{ ucfirst(auth()->user()->verification_status ?? 'pendiente') }}
                </span>
            </div>

            @if(auth()->user()->verification_result)
                <p class="text-sm text-gray-700">Resultado: {{ auth()->user()->verification_result }}</p>
            @endif
        </div>

        <form method="post" action="{{ route('profile.verification') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf

            <div>
                <label for="document_photo" class="block text-sm font-medium text-gray-700">Documento de identidad</label>
                <input id="document_photo" name="document_photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-600" required>
            </div>

            <div>
                <label for="selfie_photo" class="block text-sm font-medium text-gray-700">Selfie actual</label>
                <input id="selfie_photo" name="selfie_photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-600" required>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Enviar verificación
                </button>
            </div>
        </form>
    </section>
@endif
