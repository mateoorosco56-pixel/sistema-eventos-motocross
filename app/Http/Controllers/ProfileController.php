<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function storeVerification(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'piloto') {
            abort(403);
        }

        $request->validate([
            'identity_document_number' => ['required', 'string', 'max:30', 'regex:/\A[A-Za-z0-9 \/-]+\z/'],
        ]);
        $request->merge([
            'identity_document_number' => strtoupper(preg_replace('/[\s\/-]+/', '', $request->input('identity_document_number'))),
        ]);

        $request->validate([
            'first_name' => ['required', 'string', 'max:127'],
            'last_name' => ['required', 'string', 'max:127'],
            'identity_document_number' => [
                'required',
                'alpha_num',
                'min:4',
                'max:30',
                Rule::unique('users', 'identity_document_number')->ignore($user->id),
            ],
            'document_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'document_back_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'selfie_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
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

        $firstName = trim($request->string('first_name')->toString());
        $lastName = trim($request->string('last_name')->toString());
        $documentNumber = trim($request->string('identity_document_number')->toString());
        $user->update([
            'name' => $firstName.' '.$lastName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'identity_document_number' => $documentNumber,
            'identity_processing_consent_at' => now(),
        ]);

        $result = app(\App\Services\VerificacionIdentidadService::class)->verifyPilotIdentity(
            $documentPath,
            $documentBackPath,
            $selfiePath,
            $documentNumber,
            $firstName,
            $lastName
        );

        $user->update([
            'document_path' => $documentPath,
            'document_back_path' => $documentBackPath,
            'selfie_path' => $selfiePath,
            'verification_status' => $result['status'],
            'verification_method' => $result['method'],
            'verification_result' => $result['result'],
            'verified_at' => $result['verified_at'],
            'verification_reviewed_at' => $result['reviewed_at'],
            'verification_reviewed_by' => null,
            'verification_rejection_reason' => null,
        ]);

        return back()->with('status', 'verification-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
