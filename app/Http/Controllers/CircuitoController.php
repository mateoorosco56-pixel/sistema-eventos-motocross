<?php

namespace App\Http\Controllers;

use App\Models\Circuito;
use App\Models\Evento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CircuitoController extends Controller
{
    public function publicIndex(): View
    {
        $circuits = Circuito::with('organizer')
            ->withCount('events')
            ->orderBy('name')
            ->get();

        return view('circuitos.index', compact('circuits'));
    }

    public function publicShow(Circuito $circuit): View
    {
        $circuit->load('organizer');
        $events = Evento::query()
            ->with(['categories', 'category'])
            ->withCount(['registrations as active_registrations_count' => fn ($query) => $query->active()])
            ->where('circuit_id', $circuit->id)
            ->whereIn('status', ['programado', 'en_curso', 'finalizado'])
            ->orderBy('event_date')
            ->get();

        return view('circuitos.show', compact('circuit', 'events'));
    }

    public function index(): View
    {
        $circuits = Circuito::where('organizer_id', Auth::id())
            ->withCount('events')
            ->latest()
            ->get();

        return view('organizador.circuitos.index', compact('circuits'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $validated['organizer_id'] = Auth::id();
        $validated['image_path'] = $request->file('image')->store('circuits', 'public');
        Circuito::create($validated);

        return redirect()->route('organizador.circuitos.index')->with('success', 'Circuito agregado a tu biblioteca.');
    }

    public function destroy(Circuito $circuit): RedirectResponse
    {
        abort_unless($circuit->organizer_id === Auth::id(), 404);

        if ($circuit->events()->exists()) {
            return back()->withErrors(['circuit' => 'No puedes eliminar un circuito que ya está asignado a un evento.']);
        }

        Storage::disk('public')->delete($circuit->image_path);
        $circuit->delete();

        return redirect()->route('organizador.circuitos.index')->with('success', 'Circuito eliminado.');
    }
}