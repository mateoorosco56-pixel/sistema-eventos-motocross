<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParticipantController extends Controller
{
    public function index(): View
    {
        $participants = Participant::latest()->get();

        return view('participantes.index', compact('participants'));
    }

    public function create(): View
    {
        return view('participantes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['required', 'string', 'max:255', 'unique:participants,document'],
            'email' => ['required', 'email', 'unique:participants,email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'license' => ['nullable', 'string', 'max:255'],
            'team' => ['nullable', 'string', 'max:255'],
        ]);

        Participant::create($validated);

        return redirect()->route('participantes.index')->with('success', 'Participante creado correctamente.');
    }

    public function edit(Participant $participant): View
    {
        return view('participantes.edit', compact('participant'));
    }

    public function update(Request $request, Participant $participant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['required', 'string', 'max:255', 'unique:participants,document,' . $participant->id],
            'email' => ['required', 'email', 'unique:participants,email,' . $participant->id],
            'phone' => ['nullable', 'string', 'max:255'],
            'license' => ['nullable', 'string', 'max:255'],
            'team' => ['nullable', 'string', 'max:255'],
        ]);

        $participant->update($validated);

        return redirect()->route('participantes.index')->with('success', 'Participante actualizado correctamente.');
    }

    public function destroy(Participant $participant): RedirectResponse
    {
        $participant->delete();

        return redirect()->route('participantes.index')->with('success', 'Participante eliminado correctamente.');
    }
}
