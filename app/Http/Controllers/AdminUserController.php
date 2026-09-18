<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::latest()->get();

        return view('admin.users', compact('users'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['sometimes', 'in:usuario,piloto,organizador,administrador'],
            'verification_status' => ['sometimes', 'in:pendiente,verificada,rechazada'],
        ]);

        if (isset($validated['role']) && $user->is(auth()->user()) && $validated['role'] !== 'administrador') {
            return back()->withErrors(['role' => 'No puedes quitarte el rol de administrador.']);
        }

        if (isset($validated['verification_status'])) {
            $user->verification_status = $validated['verification_status'];

            if ($validated['verification_status'] === 'verificada') {
                $user->verified_at = now();
            } else {
                $user->verified_at = null;
            }
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

        return response()->file(Storage::disk('local')->path($user->document_path));
    }

    public function viewSelfie(User $user)
    {
        abort_unless($user->role === 'piloto' && $user->selfie_path, 404);
        abort_unless(Storage::disk('local')->exists($user->selfie_path), 404);

        return response()->file(Storage::disk('local')->path($user->selfie_path));
    }
}