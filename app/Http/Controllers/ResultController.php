<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\ResultPublished;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(): View
    {
        $isManagementView = Auth::check() && in_array(Auth::user()->role, ['organizador', 'administrador'], true);

        $events = Event::with([
            'category',
            'categories',
            'registrations' => function ($query) use ($isManagementView) {
                $query->with(['participant', 'category'])
                    ->when(! $isManagementView, function ($resultQuery) {
                        $resultQuery->where(function ($query) {
                            $query->whereNotNull('result_position')->orWhereNotNull('result_points');
                        });
                    });
            },
        ])
            ->when($isManagementView, function ($query) {
                $query->whereIn('status', ['en_curso', 'finalizado']);
            }, function ($query) {
                $query->where('results_published', true)->where('status', 'finalizado');
            })
            ->latest('event_date')
            ->get();

        return view('resultados.index', compact('events'));
    }

    public function update(Request $request, Registration $registration): RedirectResponse
    {
        $validated = $request->validate([
            'result_position' => ['nullable', 'integer', 'min:1'],
            'result_points' => ['nullable', 'integer', 'min:0'],
        ]);

        $registration->update($validated);

        $event = $registration->event;
        $event->update(['results_published' => true]);

        User::whereIn('role', ['usuario', 'piloto'])
            ->get()
            ->each(fn (User $user) => $user->notify(new ResultPublished($event->id, $event->title)));

        return redirect()->route('resultados.index')->with('success', 'Resultado actualizado correctamente.');
    }

    public function destroy(Registration $registration): RedirectResponse
    {
        $event = $registration->event;
        $participantName = $registration->participant->name ?? 'Participante';

        $registration->update([
            'result_position' => null,
            'result_points' => null,
        ]);

        return redirect()->route('resultados.index')->with('success', "Resultado de {$participantName} eliminado correctamente.");
    }
}