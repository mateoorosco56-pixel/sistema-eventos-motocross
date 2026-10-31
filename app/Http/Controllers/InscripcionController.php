<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Categoria;
use App\Models\Participante;
use App\Models\Inscripcion;
use App\Models\User;
use App\Notifications\NuevaSolicitudInscripcion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InscripcionController extends Controller
{
    public function index(Request $request): View
    {
        $registrations = $this->registrationListQuery($request)->get();
        $categories = Categoria::orderBy('name')->get();
        $events = Evento::whereHas('registrations')->orderBy('title')->get();

        return view('inscripciones.index', compact('registrations', 'categories', 'events'));
    }

    public function export(Request $request)
    {
        $registrations = $this->registrationListQuery($request)->get();

        return response()->streamDownload(function () use ($registrations) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Evento', 'Piloto', 'Documento', 'Categoría', 'Estado de inscripción', 'Estado de pago', 'Fecha de inscripción'], ';', '"', '\\');

            foreach ($registrations as $registration) {
                fputcsv($output, [
                    $registration->event->title ?? 'Evento',
                    $registration->participant->name ?? 'Participante',
                    $registration->participant->document ?? '',
                    $registration->category->name ?? '',
                    $registration->status,
                    $registration->payment_status ?? 'pendiente',
                    $registration->created_at?->format('d/m/Y H:i'),
                ], ';', '"', '\\');
            }

            fclose($output);
        }, 'inscripciones.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create(Request $request): View
    {
        $events = Evento::where('status', 'programado')->with(['categories', 'category', 'circuit'])->latest()->get();
        $occupiedDorsals = Inscripcion::query()
            ->whereIn('event_id', $events->pluck('id'))
            ->whereIn('status', Inscripcion::ACTIVE_STATUSES)
            ->whereNotNull('category_id')
            ->whereNotNull('dorsal')
            ->get(['event_id', 'category_id', 'dorsal'])
            ->groupBy('event_id')
            ->map(fn ($registrations) => $registrations->groupBy('category_id')->map(fn ($items) => $items->pluck('dorsal')->values()))
            ->all();
        $participants = Participante::all();
        $selectedEvent = $request->filled('event_id')
            ? Evento::with(['categories', 'category', 'circuit'])->find($request->integer('event_id'))
            : null;
        $categories = $selectedEvent?->categories ?? collect();
        if ($categories->isEmpty() && $selectedEvent?->category_id) {
            $categories = \App\Models\Categoria::whereKey($selectedEvent->category_id)->get();
        }
        $selectedEventId = $request->query('event_id');
        $isPilot = Auth::check() && Auth::user()->role === 'piloto';

        return view('inscripciones.create', compact('events', 'participants', 'categories', 'selectedEvent', 'selectedEventId', 'isPilot', 'occupiedDorsals'));
    }

    public function store(Request $request): RedirectResponse
    {
        $isPilot = Auth::check() && Auth::user()->role === 'piloto';

        $event = $request->filled('event_id')
            ? Evento::with('categories')->find($request->integer('event_id'))
            : null;

        if (! $event) {
            throw ValidationException::withMessages([
                'event_id' => 'El evento seleccionado no existe.',
            ]);
        }

        if ($event->status !== 'programado') {
            throw ValidationException::withMessages([
                'event_id' => 'Este evento no está abierto para inscripciones.',
            ]);
        }

        if ($event->registration_deadline && $event->registration_deadline->isBefore(today())) {
            throw ValidationException::withMessages([
                'event_id' => 'El plazo de inscripción para este evento ya terminó.',
            ]);
        }

        $verificationStatus = Auth::user()->verification_status;

        if ($isPilot && $verificationStatus !== 'verified') {
            throw ValidationException::withMessages([
                'verification_status' => 'Tu cuenta todavía no está verificada. Debes completar la verificación de identidad antes de inscribirte.',
            ]);
        }

        $rules = [
            'event_id' => ['required', 'exists:eventos,id'],
            'participant_id' => ['nullable', 'exists:participantes,id'],
            'status' => ['sometimes', 'in:pendiente,confirmada,rechazada'],
        ];

        if ($isPilot) {
            $rules = array_merge($rules, [
                'name' => [$request->filled('participant_id') ? 'nullable' : 'required', 'string', 'max:255'],
                'document' => [$request->filled('participant_id') ? 'nullable' : 'required', 'string', 'max:50'],
                'email' => [$request->filled('participant_id') ? 'nullable' : 'required', 'email', 'max:255'],
                'phone' => [$request->filled('participant_id') ? 'nullable' : 'required', 'string', 'max:50'],
                'team' => ['nullable', 'string', 'max:100'],
                'category_id' => [$request->filled('participant_id') ? 'nullable' : 'required', function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $event = Evento::whereKey($request->integer('event_id'))->first();
                    $isAvailable = $event && ($event->category_id == $value || $event->categories()->whereKey($value)->exists());
                    if (! $isAvailable) {
                        $fail('La categoría no está disponible para este evento.');
                    }
                }],
                'dorsal' => [
                    $request->filled('participant_id') ? 'nullable' : 'required',
                    'integer',
                    'min:1',
                    function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                        if ($request->filled('category_id') && $this->dorsalIsTaken($request->integer('event_id'), $request->integer('category_id'), (int) $value)) {
                            $fail($this->dorsalErrorMessage($request->integer('category_id'), (int) $value));
                        }
                    },
                ],
            ]);
        }

        $validated = $request->validate($rules);

        if ($isPilot) {
            $user = Auth::user();

            if ($event->max_participants !== null
                && $event->registrations()->whereIn('status', Inscripcion::ACTIVE_STATUSES)->count() >= $event->max_participants) {
                throw ValidationException::withMessages([
                    'event_id' => 'Este evento ya alcanzó el máximo de participantes.',
                ]);
            }

            if ($event->registrations()->where('user_id', $user->id)->whereIn('status', Inscripcion::ACTIVE_STATUSES)->exists()) {
                throw ValidationException::withMessages([
                    'event_id' => 'Ya tienes una inscripción activa para este evento.',
                ]);
            }

            if (!empty($validated['participant_id']) && Participante::where('id', $validated['participant_id'])->exists()) {
                $participant = Participante::find($validated['participant_id']);
            } else {
                $participant = Participante::create([
                    'name' => $validated['name'],
                    'document' => $validated['document'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'team' => $validated['team'] ?? '',
                ]);
            }

            $validated['participant_id'] = $participant->id;
            $validated['user_id'] = Auth::id();
            $validated['category_id'] = $validated['category_id'] ?? null;
            $validated['dorsal'] = $validated['dorsal'] ?? null;
            $validated['status'] = 'pendiente';
        }

        try {
            $registration = Inscripcion::create($validated);
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'dorsal' => $this->dorsalErrorMessage((int) ($validated['category_id'] ?? 0), (int) ($validated['dorsal'] ?? 0)),
            ]);
        }

        $organizers = User::where('role', 'organizador')->get();

        foreach ($organizers as $organizer) {
            $organizer->notify(new NuevaSolicitudInscripcion(
                $registration->id,
                $registration->event->title ?? 'Evento',
                $registration->participant->name ?? 'Participante',
            ));
        }

        if ($isPilot) {
            return redirect()->route('piloto.inscripciones.show', $registration)
                ->with([
                    'focus_payment' => true,
                ]);
        }

        return redirect()->route('inscripciones.index')->with('success', 'Inscripción registrada correctamente.');
    }

    public function edit(Inscripcion $registration): View
    {
        $registration->load(['event.categories', 'participant', 'category']);

        if (Auth::user()?->role === 'piloto' && ! $this->belongsToPilot($registration)) {
            abort(403);
        }

        $categoryIds = $registration->event
            ? DB::table('categoria_evento')->where('event_id', $registration->event->id)->pluck('category_id')->all()
            : [];

        if ($registration->event?->category_id) {
            $categoryIds[] = $registration->event->category_id;
        }

        $categories = Categoria::whereIn('id', array_unique($categoryIds))
            ->orderBy('name')
            ->get();

        $occupiedDorsals = Inscripcion::query()
            ->where('event_id', $registration->event_id)
            ->where('category_id', $registration->category_id)
            ->whereIn('status', Inscripcion::ACTIVE_STATUSES)
            ->whereNotNull('dorsal')
            ->where('id', '<>', $registration->id)
            ->pluck('dorsal')
            ->values();

        return view('inscripciones.edit', compact('registration', 'categories', 'occupiedDorsals'));
    }

    public function show(Inscripcion $registration): View
    {
        $registration->load(['event.categories', 'participant', 'category']);

        if (Auth::user()?->role === 'piloto' && ! $this->belongsToPilot($registration)) {
            abort(403);
        }

        return view('inscripciones.show', compact('registration'));
    }

    public function update(Request $request, Inscripcion $registration): RedirectResponse
    {
        if (Auth::user()?->role === 'piloto') {
            if (! $this->belongsToPilot($registration)) {
                abort(403);
            }

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'document' => ['required', 'string', 'max:50'],
                'email' => ['required', 'email', 'max:255', 'in:' . Auth::user()->email],
                'phone' => ['required', 'string', 'max:50'],
                'team' => ['nullable', 'string', 'max:100'],
                'category_id' => ['required', 'exists:categorias,id'],
                'dorsal' => ['required', 'integer', 'min:1'],
            ]);

            $eventHasCategory = $registration->event
                && ($registration->event->category_id == $validated['category_id']
                    || $registration->event->categories()->whereKey($validated['category_id'])->exists());

            if (! $eventHasCategory) {
                return back()->withErrors(['category_id' => 'La categoría no está disponible para este evento.'])->withInput();
            }

            if ($this->dorsalIsTaken($registration->event_id, (int) $validated['category_id'], (int) $validated['dorsal'], $registration->id)) {
                return back()->withErrors(['dorsal' => $this->dorsalErrorMessage((int) $validated['category_id'], (int) $validated['dorsal'])])->withInput();
            }

            $participant = Participante::create([
                'name' => $validated['name'],
                'document' => $validated['document'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'team' => $validated['team'] ?? '',
            ]);
            $registration->update([
                'participant_id' => $participant->id,
                'category_id' => $validated['category_id'],
                'dorsal' => $validated['dorsal'],
            ]);

            return redirect()->route('inscripciones.index')->with('success', 'Inscripción actualizada correctamente.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pendiente,confirmada,rechazada'],
            'dorsal' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $dorsal = array_key_exists('dorsal', $validated) ? $validated['dorsal'] : $registration->dorsal;
        if (in_array($validated['status'], Inscripcion::ACTIVE_STATUSES, true) && $dorsal !== null && $registration->category_id !== null
            && $this->dorsalIsTaken($registration->event_id, $registration->category_id, (int) $dorsal, $registration->id)) {
            return back()->withErrors(['dorsal' => $this->dorsalErrorMessage($registration->category_id, (int) $dorsal)])->withInput();
        }

        $registration->update($validated);

        return redirect()->route('inscripciones.index')->with('success', 'Estado de inscripción actualizado.');
    }

    public function submitPayment(Request $request, Inscripcion $registration): RedirectResponse
    {
        if (! $this->belongsToPilot($registration)) {
            abort(403);
        }

        $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        if ($registration->payment_proof_path) {
            Storage::disk('local')->delete($registration->payment_proof_path);
        }

        $registration->update([
            'payment_proof_path' => $request->file('payment_proof')->store('payment-proofs'),
            'payment_status' => 'en_revision',
            'payment_submitted_at' => now(),
        ]);

        return back()->with([
            'success' => 'Comprobante enviado. El organizador revisará tu pago.',
            'payment_submitted' => true,
        ]);
    }

    public function validatePayment(Request $request, Inscripcion $registration): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:aprobado,rechazado'],
            'status' => ['sometimes', 'nullable', 'in:pendiente,confirmada,rechazada'],
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = $validated['status'] ?? ($validated['payment_status'] === 'aprobado' ? 'confirmada' : $registration->status);

        if (in_array($status, Inscripcion::ACTIVE_STATUSES, true)
            && $registration->dorsal !== null
            && $registration->category_id !== null
            && $this->dorsalIsTaken($registration->event_id, $registration->category_id, (int) $registration->dorsal, $registration->id)) {
            return back()->withErrors([
                'payment_status' => $this->dorsalErrorMessage($registration->category_id, (int) $registration->dorsal),
            ]);
        }

        $registration->update([
            'payment_status' => $validated['payment_status'],
            'status' => $status,
            'cancellation_reason' => $validated['cancellation_reason'] ?? null,
        ]);

        if ($validated['payment_status'] === 'aprobado') {
            return back()->with('success', 'Pago aprobado e inscripción confirmada.');
        }

        if (($validated['status'] ?? null) === 'rechazada') {
            return back()->with('success', 'Inscripción rechazada correctamente.');
        }

        return back()->with('success', 'Pago rechazado correctamente.');
    }

    public function downloadPaymentProof(Inscripcion $registration)
    {
        abort_unless(
            $registration->payment_proof_path
                && Storage::disk('local')->exists($registration->payment_proof_path),
            404,
        );

        return response()->download(Storage::disk('local')->path($registration->payment_proof_path));
    }

    public function viewPaymentProof(Inscripcion $registration)
    {
        abort_unless(
            Auth::user()?->role === 'piloto'
                ? $this->belongsToPilot($registration)
                : in_array(Auth::user()?->role, ['organizador', 'administrador'], true),
            403,
        );

        abort_unless(
            $registration->payment_proof_path
                && Storage::disk('local')->exists($registration->payment_proof_path),
            404,
        );

        return response()->file(Storage::disk('local')->path($registration->payment_proof_path));
    }

    public function destroy(Inscripcion $registration): RedirectResponse
    {
        $registration->delete();

        return redirect()->route('inscripciones.index')->with('success', 'Inscripción eliminada correctamente.');
    }

    public function cancel(Inscripcion $registration): RedirectResponse
    {
        if (! $this->belongsToPilot($registration)) {
            abort(403);
        }

        if ($registration->status !== 'pendiente') {
            return redirect()->route('inscripciones.index')->with('error', 'Solo puedes cancelar inscripciones pendientes.');
        }

        $reason = request()->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ])['cancellation_reason'] ?? 'Cancelada por el piloto.';

        $registration->update([
            'status' => 'cancelada',
            'cancellation_reason' => $reason,
        ]);

        return redirect()->route('inscripciones.index')->with('success', 'Inscripción cancelada correctamente.');
    }

    public function readNotification(Request $request, string $notification): RedirectResponse
    {
        $userNotification = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $userNotification->markAsRead();

        $url = $userNotification->data['url']
            ?? (isset($userNotification->data['registration_id'])
                ? route('inscripciones.edit', $userNotification->data['registration_id'])
                : route('inscripciones.index'));

        return redirect($url);
    }

    private function belongsToPilot(Inscripcion $registration): bool
    {
        $user = Auth::user();

        return $registration->user_id === $user?->id
            || $registration->participant?->email === $user?->email
            || $registration->participant?->name === $user?->name;
    }

    private function registrationListQuery(Request $request)
    {
        return Inscripcion::with(['event.categories', 'event.category', 'event.circuit', 'participant', 'category'])
            ->when($request->filled('event_id'), fn ($query) => $query->where('event_id', $request->integer('event_id')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when(in_array($request->input('status'), ['pendiente', 'confirmada', 'rechazada', 'cancelada'], true), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->toString();
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->whereHas('participant', fn ($participantQuery) => $participantQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('document', 'like', "%{$search}%"))
                        ->orWhereHas('event', fn ($eventQuery) => $eventQuery->where('title', 'like', "%{$search}%"));
                });
            })
            ->when(Auth::user()?->role === 'piloto', function ($query) {
                $query->where(function ($registrationQuery) {
                    $registrationQuery->where('user_id', Auth::id())
                        ->orWhereHas('participant', function ($participantQuery) {
                            $participantQuery->where('email', Auth::user()->email)
                                ->orWhere('name', Auth::user()->name);
                        });
                });
            })
            ->latest();
    }

    private function dorsalIsTaken(int $eventId, int $categoryId, int $dorsal, ?int $ignoreRegistrationId = null): bool
    {
        return Inscripcion::query()
            ->where('event_id', $eventId)
            ->where('category_id', $categoryId)
            ->where('dorsal', $dorsal)
            ->whereIn('status', Inscripcion::ACTIVE_STATUSES)
            ->when($ignoreRegistrationId, fn ($query) => $query->where('id', '<>', $ignoreRegistrationId))
            ->exists();
    }

    private function dorsalErrorMessage(int $categoryId, int $dorsal): string
    {
        $categoryName = Categoria::find($categoryId)?->name ?? 'esta categoría';

        return "El dorsal {$dorsal} ya está ocupado en la categoría {$categoryName}. Por favor, selecciona otro número.";
    }
}
