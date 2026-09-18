<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Category;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\EventPublished;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        Event::query()
            ->whereIn('status', ['programado', 'en_curso'])
            ->get()
            ->each(fn (Event $event) => $event->syncStatusByDate());

        $query = Event::with(['categories', 'category'])
            ->whereIn('status', ['programado', 'en_curso', 'finalizado']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($eventQuery) use ($search) {
                $eventQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhereHas('categories', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('category')) {
            $query->where(function ($eventQuery) use ($request) {
                $eventQuery->where('category_id', $request->integer('category'))
                    ->orWhereHas('categories', fn ($categoryQuery) => $categoryQuery->whereKey($request->integer('category')));
            });
        }

        if ($request->input('status') === 'proximos') {
            $query->whereIn('status', ['programado', 'en_curso']);
        } elseif ($request->input('status') === 'finalizados') {
            $query->where('status', 'finalizado');
        }

        $events = $query->orderByRaw("CASE WHEN status = 'programado' THEN 0 WHEN status = 'en_curso' THEN 1 ELSE 2 END")
            ->orderBy('event_date')
            ->get();

        if (Auth::check() && Auth::user()->role === 'piloto') {
            $events = $events->filter(function ($event) {
                return $event->status === 'programado';
            });

            Auth::user()->unreadNotifications
                ->where('type', \App\Notifications\EventPublished::class)
                ->each(fn ($notification) => $notification->markAsRead());
        }

        $categories = Category::orderBy('name')->get();

        return view('eventos.index', compact('events', 'categories'));
    }

    public function programados(): View
    {
        $events = Event::where('status', 'programado')->latest()->get();

        return view('eventos.programados', compact('events'));
    }

    public function show(Event $event): View
    {
        $event->syncStatusByDate();
        abort_unless(in_array($event->status, ['programado', 'en_curso', 'finalizado'], true), 404);

        $event->load('categories');

        return view('eventos.show', compact('event'));
    }

    public function create(): View
    {
        return view('eventos.create', ['categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'event_date' => ['required', 'date'],
            'registration_deadline' => ['nullable', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'entry_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_qr' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:programado,en_curso,finalizado,cancelado'],
        ]);

        $categoryIds = $validated['category_ids'];
        unset($validated['category_ids']);

        if ($request->hasFile('payment_qr')) {
            $validated['payment_qr_path'] = $request->file('payment_qr')->store('payment-qrs', 'public');
        }

        $event = Event::create($validated);
        $event->categories()->sync($categoryIds);

        if ($event->status === 'programado') {
            User::whereIn('role', ['usuario', 'piloto'])
                ->get()
                ->each(fn (User $user) => $user->notify(new EventPublished($event->id, $event->title)));
        }

        return redirect()->route('eventos.index')->with('success', 'Evento creado correctamente.');
    }

    public function edit(Event $event): View
    {
        return view('eventos.edit', [
            'event' => $event->load('categories'),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'event_date' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'entry_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_qr' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:programado,en_curso,finalizado,cancelado'],
        ]);

        $wasPublished = $event->status === 'programado';
        $categoryIds = $validated['category_ids'];
        unset($validated['category_ids']);

        if ($request->hasFile('payment_qr')) {
            if ($event->payment_qr_path) {
                Storage::disk('public')->delete($event->payment_qr_path);
            }

            $validated['payment_qr_path'] = $request->file('payment_qr')->store('payment-qrs', 'public');
        }

        $event->update($validated);
        $event->categories()->sync($categoryIds);

        if (!$wasPublished && $event->status === 'programado') {
            User::whereIn('role', ['usuario', 'piloto'])
                ->get()
                ->each(fn (User $user) => $user->notify(new EventPublished($event->id, $event->title)));
        }

        return redirect()->route('eventos.index')->with('success', 'Evento actualizado correctamente.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('eventos.index')->with('success', 'Evento eliminado correctamente.');
    }

    public function myEvents(): View
    {
        $participations = Registration::with(['event', 'participant'])
            ->where('status', 'confirmada')
            ->latest()
            ->get()
            ->filter(function ($registration) {
                return $registration->event !== null;
            });

        $upcomingEvents = $participations->filter(fn ($registration) => $registration->event->status === 'programado');
        $activeEvents = $participations->filter(fn ($registration) => $registration->event->status === 'en_curso');
        $finishedEvents = $participations->filter(fn ($registration) => $registration->event->status === 'finalizado');

        return view('piloto.mis-eventos', compact('participations', 'upcomingEvents', 'activeEvents', 'finishedEvents'));
    }

    public function results(Event $event): View
    {
        $hasPublishedResultData = $event->registrations()
            ->where(function ($query) {
                $query->whereNotNull('result_position')->orWhereNotNull('result_points');
            })
            ->exists();

        if ((! Auth::check() || Auth::user()->role === 'usuario') && ! $event->results_published && ! $hasPublishedResultData) {
            abort(404);
        }

        $event->syncStatusByDate();

        $registrations = Registration::with('participant')
            ->where('event_id', $event->id)
            ->where('status', 'confirmada')
            ->where(function ($query) {
                $query->whereNotNull('result_position')->orWhereNotNull('result_points');
            })
            ->orderByRaw('result_position IS NULL, result_position')
            ->latest()
            ->get();

        return view('piloto.resultados', compact('event', 'registrations'));
    }

    public function ranking(Event $event): View
    {
        if ((! Auth::check() || Auth::user()->role === 'usuario') && ! $event->results_published) {
            abort(404);
        }

        $event->syncStatusByDate();

        $registrations = Registration::with('participant')
            ->where('event_id', $event->id)
            ->where('status', 'confirmada')
            ->where('participated', true)
            ->get()
            ->sortBy(function ($registration) {
                return [$registration->result_position ?? PHP_INT_MAX, $registration->id];
            });

        $ranking = $registrations->values()->map(function ($registration, $index) {
            return [
                'position' => $index + 1,
                'participant' => $registration->participant->name ?? 'Participante',
                'points' => $registration->result_points,
            ];
        });

        return view('piloto.ranking', compact('event', 'ranking'));
    }

    public function organizerEvents(): View
    {
        Event::query()
            ->whereIn('status', ['programado', 'en_curso'])
            ->get()
            ->each(fn (Event $event) => $event->syncStatusByDate());

        $createdEvents = Event::latest('created_at')->get();

        $upcomingEvents = $createdEvents->where('status', 'programado');
        $activeEvents = $createdEvents->where('status', 'en_curso');
        $finishedEvents = $createdEvents->where('status', 'finalizado');

        return view('organizador.mis-eventos', compact('upcomingEvents', 'activeEvents', 'finishedEvents'));
    }
}
