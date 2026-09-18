<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $publishedStatuses = ['programado', 'en_curso', 'finalizado'];

        Event::query()
            ->whereIn('status', ['programado', 'en_curso'])
            ->get()
            ->each(fn (Event $event) => $event->syncStatusByDate());

        $events = Event::with(['categories', 'category'])
            ->whereIn('status', $publishedStatuses)
            ->latest('event_date')
            ->get();

        $upcomingEvent = Event::with(['categories', 'category'])
            ->where('status', 'programado')
            ->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->first();

        $resultEvents = Event::with(['registrations' => function ($query) {
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

        return view('welcome', [
            'events' => $events,
            'upcomingEvent' => $upcomingEvent,
            'resultEvents' => $resultEvents,
            'categories' => Category::orderBy('name')->get(),
            'stats' => [
                'events' => Event::whereIn('status', $publishedStatuses)->count(),
                'categories' => Category::count(),
                'participants' => Participant::count(),
                'results' => Event::where('results_published', true)->count(),
            ],
        ]);
    }
}