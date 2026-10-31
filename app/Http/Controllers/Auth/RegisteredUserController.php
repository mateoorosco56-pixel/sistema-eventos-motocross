<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\VerificacionIdentidadService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'identity_document_number' => ['required', 'string', 'max:30', 'regex:/\A[A-Za-z0-9 \/-]+\z/'],
        ]);
        $request->merge([
            'identity_document_number' => strtoupper(preg_replace('/[\s\/-]+/', '', $request->input('identity_document_number'))),
        ]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:127'],
            'last_name' => ['required', 'string', 'max:127'],
            'identity_document_number' => ['required', 'alpha_num', 'min:4', 'max:30', 'unique:users,identity_document_number'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'document_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'document_back_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'selfie_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'identity_processing_consent' => ['accepted'],
        ]);

        $documentPath = $request->file('document_photo')->store('verification-documents', 'local');
        if (! $documentPath) {
            throw new \RuntimeException('No se pudo guardar el carnet en el almacenamiento privado.');
        }

        $documentBackPath = $request->file('document_back_photo')->store('verification-documents', 'local');
        if (! $documentBackPath) {
            Storage::disk('local')->delete($documentPath);

            throw new \RuntimeException('No se pudo guardar el reverso del carnet en el almacenamiento privado.');
        }

        $selfiePath = $request->file('selfie_photo')->store('verification-selfies', 'local');
        if (! $selfiePath) {
            Storage::disk('local')->delete([$documentPath, $documentBackPath]);

            throw new \RuntimeException('No se pudieron guardar los archivos privados de verificación de identidad.');
        }

        $user = User::create([
            'name' => trim($validated['first_name']).' '.trim($validated['last_name']),
            'first_name' => trim($validated['first_name']),
            'last_name' => trim($validated['last_name']),
            'identity_document_number' => trim($validated['identity_document_number']),
            'email' => $validated['email'],
            'role' => 'piloto',
            'verification_status' => 'pending_review',
            'identity_processing_consent_at' => now(),
            'password' => Hash::make($validated['password']),
        ]);

        $verification = app(VerificacionIdentidadService::class)->verifyPilotIdentity(
            $documentPath,
            $documentBackPath,
            $selfiePath,
            $user->identity_document_number,
            $user->first_name,
            $user->last_name
        );

        $user->update([
            'document_path' => $documentPath,
            'document_back_path' => $documentBackPath,
            'selfie_path' => $selfiePath,
            'verification_status' => $verification['status'],
            'verification_method' => $verification['method'],
            'verification_result' => $verification['result'],
            'verified_at' => $verification['verified_at'],
            'verification_reviewed_at' => $verification['reviewed_at'],
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
