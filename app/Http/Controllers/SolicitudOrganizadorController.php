<?php

namespace App\Http\Controllers;

use App\Models\SolicitudOrganizador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SolicitudOrganizadorController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $request = SolicitudOrganizador::where('user_id', auth()->id())->latest()->first();

        if ($request?->status === 'pendiente' || auth()->user()->role === 'organizador') {
            return redirect()->route('dashboard');
        }

        return view('piloto.solicitud-organizador', compact('request'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        SolicitudOrganizador::updateOrCreate(
            ['user_id' => auth()->id(), 'status' => 'pendiente'],
            ['reason' => $validated['reason'] ?? null, 'admin_note' => null, 'reviewed_at' => null]
        );

        return redirect()->route('dashboard')->with('success', 'Solicitud enviada al administrador.');
    }

    public function index(): View
    {
        $requests = SolicitudOrganizador::with('user')->latest()->get();

        return view('admin.solicitudes-organizador', compact('requests'));
    }

    public function update(Request $request, SolicitudOrganizador $organizerRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:aprobada,rechazada'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $organizerRequest->update([
            'status' => $validated['status'],
            'admin_note' => $validated['admin_note'] ?? null,
            'reviewed_at' => Carbon::now(),
        ]);

        if ($validated['status'] === 'aprobada') {
            $organizerRequest->user()->update(['role' => 'organizador']);
        }

        return redirect()->route('admin.organizer-requests.index')->with('success', 'Solicitud actualizada.');
    }
}