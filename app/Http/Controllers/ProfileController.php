<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
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
            'document_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'selfie_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $documentPath = $request->file('document_photo')->store('verification-documents');
        $selfiePath = $request->file('selfie_photo')->store('verification-selfies');

        $result = app(\App\Services\IdentityVerificationService::class)->verifyPilotIdentity($documentPath, $selfiePath);

        $user->update([
            'document_path' => $documentPath,
            'selfie_path' => $selfiePath,
            'verification_status' => $result['status'],
            'verification_result' => $result['result'],
            'verified_at' => $result['verified_at'],
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
