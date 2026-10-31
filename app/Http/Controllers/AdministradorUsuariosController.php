<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdministradorUsuariosController extends Controller
{
    public function index(): View
    {
        $users = User::with('verificationReviewer')->latest()->get();

        return view('admin.usuarios', compact('users'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['sometimes', 'in:usuario,piloto,organizador,administrador'],
            'verification_status' => ['sometimes', 'in:pending_review,verified,rejected'],
            'verification_rejection_reason' => ['required_if:verification_status,rejected', 'nullable', 'string', 'max:1000'],
        ]);

        if (isset($validated['role']) && $user->is(auth()->user()) && $validated['role'] !== 'administrador') {
            return back()->withErrors(['role' => 'No puedes quitarte el rol de administrador.']);
        }

        if (isset($validated['verification_status'])) {
            if ($user->role !== 'piloto') {
                return back()->withErrors(['verification_status' => 'Solo se puede revisar la identidad de una cuenta de piloto.']);
            }

            if (
                $validated['verification_status'] === 'verified'
                && (
                    ! $user->document_path
                    || ! $user->selfie_path
                    || ! Storage::disk('local')->exists($user->document_path)
                    || ! Storage::disk('local')->exists($user->selfie_path)
                )
            ) {
                return back()->withErrors(['verification_status' => 'No se puede aprobar la identidad sin el carnet y la fotografía del rostro.']);
            }

            $user->verification_status = $validated['verification_status'];
            $user->verification_method = $validated['verification_status'] === 'pending_review'
                ? 'manual_review'
                : 'manual_admin';
            $user->verification_reviewed_by = auth()->id();
            $user->verification_reviewed_at = now();
            $user->verified_at = $validated['verification_status'] === 'verified' ? now() : null;
            $user->verification_rejection_reason = $validated['verification_status'] === 'rejected'
                ? $validated['verification_rejection_reason']
                : null;
        }

        if (isset($validated['role'])) {
            $user->role = $validated['role'];
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'No puedes eliminar tu propia cuenta desde este panel.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado correctamente.');
    }

    public function viewDocument(User $user)
    {
        abort_unless($user->role === 'piloto' && $user->document_path, 404);
        abort_unless(Storage::disk('local')->exists($user->document_path), 404);

        $response = response()->file(Storage::disk('local')->path($user->document_path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function viewDocumentBack(User $user)
    {
        abort_unless($user->role === 'piloto' && $user->document_back_path, 404);
        abort_unless(Storage::disk('local')->exists($user->document_back_path), 404);

        $response = response()->file(Storage::disk('local')->path($user->document_back_path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function viewSelfie(User $user)
    {
        abort_unless($user->role === 'piloto' && $user->selfie_path, 404);
        abort_unless(Storage::disk('local')->exists($user->selfie_path), 404);

        $response = response()->file(Storage::disk('local')->path($user->selfie_path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}