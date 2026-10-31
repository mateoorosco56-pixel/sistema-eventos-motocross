<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Circuito;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\Inscripcion;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function __invoke(): View
    {
        $publishedStatuses = ['programado', 'en_curso', 'finalizado'];

        Evento::query()
            ->whereIn('status', ['programado', 'en_curso'])
            ->get()
            ->each(fn (Evento $event) => $event->syncStatusByDate());

        $events = Evento::with([
                'categories',
                'category',
            'circuit',
                'startingOrders' => fn ($query) => $query->where('is_published', true)->with('category'),
            ])
            ->withCount(['registrations as active_registrations_count' => fn ($query) => $query->active()])
            ->whereIn('status', $publishedStatuses)
            ->latest('event_date')
            ->get();

        $upcomingEvents = Evento::with([
                'categories',
                'category',
            'circuit',
                'startingOrders' => fn ($query) => $query->where('is_published', true)->with('category'),
            ])
            ->withCount(['registrations as active_registrations_count' => fn ($query) => $query->active()])
            ->where('status', 'programado')
            ->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->get();

        $upcomingStarts = $upcomingEvents->filter(fn (Evento $event) => $event->startingOrders->isNotEmpty());

        $resultEvents = Evento::with(['registrations' => function ($query) {
                $query->with(['participant', 'category'])
                    ->where('status', 'confirmada')
                    ->where(function ($resultQuery) {
                        $resultQuery->whereNotNull('result_position')
                            ->orWhereNotNull('result_points');
                    })
                    ->orderByRaw('result_position IS NULL, result_position')
                    ->limit(3);
            }])
            ->where('results_published', true)
            ->where('status', 'finalizado')
            ->latest('event_date')
            ->take(3)
            ->get();

        $ranking = Inscripcion::query()
            ->selectRaw('participant_id, SUM(result_points) as points')
            ->whereNotNull('result_points')
            ->whereHas('event', fn ($query) => $query
                ->where('results_published', true)
                ->where('status', 'finalizado')
                ->whereYear('event_date', now()->year))
            ->groupBy('participant_id')
            ->orderByDesc('points')
            ->take(5)
            ->with('participant')
            ->get();

        $circuits = Circuito::withCount('events')
            ->orderBy('name')
            ->take(5)
            ->get();

        return view('home.index', [
            'events' => $events,
            'upcomingEvents' => $upcomingEvents,
            'upcomingStarts' => $upcomingStarts,
            'resultEvents' => $resultEvents,
            'finishedEvents' => $events->where('status', 'finalizado')->take(4),
            'ranking' => $ranking,
            'circuits' => $circuits,
            'categories' => Categoria::orderBy('name')->get(),
            'stats' => [
                'events' => Evento::whereIn('status', $publishedStatuses)->count(),
                'categories' => Categoria::count(),
                'participants' => Participante::count(),
                'results' => Evento::where('results_published', true)->count(),
            ],
        ]);
    }
}