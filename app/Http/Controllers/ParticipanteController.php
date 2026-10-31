<?php

namespace App\Http\Controllers;

use App\Models\Participante;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParticipanteController extends Controller
{
    public function index(): View
    {
        $participants = Participante::latest()->get();

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
            'document' => ['required', 'string', 'max:255', 'unique:participantes,document'],
            'email' => ['required', 'email', 'unique:participantes,email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'license' => ['nullable', 'string', 'max:255'],
            'team' => ['nullable', 'string', 'max:255'],
        ]);

        Participante::create($validated);

        return redirect()->route('participantes.index')->with('success', 'Participante creado correctamente.');
    }

    public function edit(Participante $participant): View
    {
        return view('participantes.edit', compact('participant'));
    }

    public function update(Request $request, Participante $participant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['required', 'string', 'max:255', 'unique:participantes,document,' . $participant->id],
            'email' => ['required', 'email', 'unique:participantes,email,' . $participant->id],
            'phone' => ['nullable', 'string', 'max:255'],
            'license' => ['nullable', 'string', 'max:255'],
            'team' => ['nullable', 'string', 'max:255'],
        ]);

        $participant->update($validated);

        return redirect()->route('participantes.index')->with('success', 'Participante actualizado correctamente.');
    }

    public function destroy(Participante $participant): RedirectResponse
    {
        $participant->delete();

        return redirect()->route('participantes.index')->with('success', 'Participante eliminado correctamente.');
    }
}
