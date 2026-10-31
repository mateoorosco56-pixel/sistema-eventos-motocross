<?php

namespace App\Http\Controllers;

use App\Models\Campeonato;
use App\Models\Evento;
use App\Models\PremioEvento;
use App\Models\OrdenSalidaEvento;
use App\Models\Categoria;
use App\Models\Circuito;
use App\Models\Inscripcion;
use App\Models\User;
use App\Notifications\EventoPublicado;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EventoController extends Controller
{
    public function index(Request $request): View
    {
        if (Auth::check() && Auth::user()->role === 'organizador') {
            return $this->organizerEvents();
        }

        Evento::query()
            ->whereIn('status', ['programado', 'en_curso'])
            ->get()
            ->each(fn (Evento $event) => $event->syncStatusByDate());

        $query = Evento::with(['categories', 'category', 'circuit'])
            ->withCount(['registrations as active_registrations_count' => fn ($query) => $query->active()])
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
            $query->where('status', 'programado');
        } elseif ($request->input('status') === 'en_curso') {
            $query->where('status', 'en_curso');
        } elseif ($request->input('status') === 'finalizados') {
            $query->where('status', 'finalizado');
        }

        $events = $query->orderByRaw("CASE WHEN status = 'programado' THEN 0 WHEN status = 'en_curso' THEN 1 ELSE 2 END")
            ->orderBy('event_date')
            ->get();

        if (Auth::check() && Auth::user()->role === 'piloto') {
            Auth::user()->unreadNotifications
                ->where('type', \App\Notifications\EventoPublicado::class)
                ->each(fn ($notification) => $notification->markAsRead());
        }

        $categories = Categoria::orderBy('name')->get();

        return view('eventos.index', compact('events', 'categories'));
    }

    public function programados(): View
    {
        $events = Evento::with('circuit')->where('status', 'programado')->latest()->get();

        return view('eventos.programados', compact('events'));
    }

    public function show(Evento $event): View
    {
        $event->syncStatusByDate();
        abort_unless(in_array($event->status, ['programado', 'en_curso', 'finalizado'], true), 404);

        $event->load(['categories', 'circuit', 'prizes.category', 'startingOrders.category']);
        $event->loadCount(['registrations as active_registrations_count' => fn ($query) => $query->active()]);

        $activeRegistrations = $event->registrations()
            ->with(['participant', 'category'])
            ->active()
            ->whereNotNull('category_id')
            ->get()
            ->groupBy('category_id');

        $event->startingOrders
            ->each(fn (OrdenSalidaEvento $startingOrder) => $startingOrder->setRelation(
                'registrations',
                $activeRegistrations->get($startingOrder->category_id, collect())
            ));

        $event->setRelation(
            'startingOrders',
            $event->startingOrders->where('is_published', true)->values()
        );

        return view('eventos.show', compact('event'));
    }

    public function create(): View
    {
        return view('eventos.create', [
            'categories' => Categoria::orderBy('name')->get(),
            'circuits' => Circuito::where('organizer_id', Auth::id())->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categorias,id'],
            'category_fees' => ['nullable', 'array'],
            'category_fees.*' => ['nullable', 'numeric', 'min:0'],
            'event_date' => ['nullable', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'registration_deadline' => ['nullable', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'circuit_id' => ['nullable', 'integer', Rule::exists('circuitos', 'id')->where('organizer_id', Auth::id())],
            'organizer_whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\s-]+$/'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'entry_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_qr' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'event_background' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:programado,en_curso,finalizado,cancelado'],
            'prizes' => ['nullable', 'array'],
            'prizes.*.category_id' => ['required', 'integer', 'exists:categorias,id'],
            'prizes.*.position' => ['required', 'integer', 'in:1,2,3'],
            'prizes.*.award_type' => ['required', 'string', 'max:100'],
            'prizes.*.description' => ['nullable', 'string', 'max:2000'],
            'prizes.*.amount' => ['nullable', 'numeric', 'min:0'],
            'starting_orders' => ['nullable', 'array'],
            'starting_orders.*.category_id' => ['required', 'integer', 'exists:categorias,id'],
            'starting_orders.*.position' => ['required', 'integer', 'min:1', 'max:100'],
            'starting_orders.*.scheduled_time' => ['nullable', 'date_format:H:i'],
            'publish_starting_order' => ['nullable', 'boolean'],
            'is_championship' => ['nullable', 'boolean'],
            'championship_name' => ['nullable', 'string', 'max:255'],
            'championship_start_date' => ['nullable', 'date'],
            'rounds' => ['nullable', 'integer', 'min:2', 'max:12'],
            'championship_interval_days' => ['nullable', 'integer', 'in:7,14'],
            'championship_points' => ['nullable', 'array'],
            'championship_points.*' => ['array'],
            'championship_points.*.*' => ['array'],
            'championship_points.*.*.*' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'championship_prizes' => ['nullable', 'array'],
            'championship_prizes.*' => ['array'],
            'championship_prizes.*.*.amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ];

        if (! $request->boolean('is_championship')) {
            $rules['event_date'] = ['required', 'date'];
        } else {
            $rules['championship_name'] = ['required', 'string', 'max:255'];
            $rules['championship_start_date'] = ['required', 'date'];
            $rules['rounds'] = ['required', 'integer', 'min:2', 'max:12'];
        }

        $validated = $request->validate($rules);

        $categoryIds = $this->normalizeCategoryIds($validated['category_ids'] ?? []);
        $categoryFees = $this->normalizeCategoryFees($validated['category_fees'] ?? []);
        unset($validated['category_ids']);
        unset($validated['category_fees']);
        $prizes = $this->normalizePrizeRows($validated['prizes'] ?? []);
        $championshipScoring = [];
        $championshipPrizes = [];
        if ($request->boolean('is_championship')) {
            $championshipScoring = $this->normalizeChampionshipScoring(
                $validated['championship_points'] ?? [],
                $categoryIds,
                (int) $validated['rounds']
            );
            $championshipPrizes = $this->normalizeChampionshipPrizes(
                $validated['championship_prizes'] ?? [],
                $categoryIds
            );
        }
        $startingOrders = $this->normalizeStartingOrderRows($validated['starting_orders'] ?? []);
        $publishStartingOrder = filter_var($validated['publish_starting_order'] ?? false, FILTER_VALIDATE_BOOLEAN);
        unset(
            $validated['prizes'],
            $validated['starting_orders'],
            $validated['publish_starting_order'],
            $validated['championship_points'],
            $validated['championship_prizes']
        );

        if (empty($categoryIds)) {
            throw ValidationException::withMessages([
                'category_ids' => 'Debe seleccionar al menos una categoría válida.',
            ]);
        }

        if ($request->hasFile('payment_qr')) {
            $validated['payment_qr_path'] = $request->file('payment_qr')->store('payment-qrs', 'public');
        }

        if ($request->hasFile('event_background')) {
            $validated['event_background_path'] = $request->file('event_background')->store('event-backgrounds', 'public');
        }

        if ($request->boolean('is_championship')) {
            return $this->storeAsChampionship(
                $request,
                $validated,
                $categoryIds,
                $categoryFees,
                $championshipScoring,
                $championshipPrizes,
                $startingOrders,
                $publishStartingOrder
            );
        }

        $event = Evento::create($validated);
        $event->categories()->sync(collect($categoryIds)->mapWithKeys(fn ($categoryId) => [
            $categoryId => ['entry_fee' => $categoryFees[$categoryId] ?? null],
        ])->all());
        $this->syncEventExtras($event, $prizes, $startingOrders, $categoryIds, $publishStartingOrder);

        if ($event->status === 'programado') {
            User::whereIn('role', ['usuario', 'piloto'])
                ->get()
                ->each(fn (User $user) => $user->notify(new EventoPublicado($event->id, $event->title)));
        }

        return redirect()->route('eventos.index')->with('success', 'Evento creado correctamente.');
    }

    protected function storeAsChampionship(
        Request $request,
        array $validated,
        array $categoryIds,
        array $categoryFees,
        array $championshipScoring,
        array $championshipPrizes,
        array $startingOrders,
        bool $publishStartingOrder
    ): RedirectResponse {
        $championshipName = trim((string) ($validated['championship_name'] ?? $validated['title'] ?? 'Campeonato'));
        $rounds = max(2, (int) ($validated['rounds'] ?? 6));
        $startDate = Carbon::parse($validated['championship_start_date'] ?? $validated['event_date'] ?? now());
        $intervalDays = max(1, (int) ($validated['championship_interval_days'] ?? 7));

        return DB::transaction(function () use (
            $championshipName,
            $rounds,
            $startDate,
            $intervalDays,
            $validated,
            $categoryIds,
            $categoryFees,
            $championshipScoring,
            $championshipPrizes,
            $startingOrders,
            $publishStartingOrder
        ): RedirectResponse {
            $championship = Campeonato::create([
                'name' => $championshipName,
                'description' => $validated['description'] ?? null,
                'start_date' => $startDate->toDateString(),
                'total_rounds' => $rounds,
                'status' => $validated['status'] ?? 'programado',
                'organizer_id' => Auth::id(),
            ]);
            $championship->scoringRules()->createMany($championshipScoring);

            foreach (range(1, $rounds) as $roundNumber) {
                $eventDate = $startDate->copy()->addDays(($roundNumber - 1) * $intervalDays);
                $trophyPrizes = collect($categoryIds)
                    ->flatMap(fn ($categoryId) => collect([1, 2, 3])->map(fn ($position) => [
                        'category_id' => $categoryId,
                        'position' => $position,
                        'award_type' => 'Trofeo',
                        'description' => null,
                        'amount' => null,
                    ]))
                    ->all();

                $event = Evento::create([
                    ...$validated,
                    'title' => $championshipName . ' - Fecha ' . $roundNumber,
                    'event_date' => $eventDate->toDateString(),
                    'championship_id' => $championship->id,
                    'round_number' => $roundNumber,
                    'payment_qr_path' => $validated['payment_qr_path'] ?? null,
                    'event_background_path' => $validated['event_background_path'] ?? null,
                ]);

                $event->categories()->sync(collect($categoryIds)->mapWithKeys(fn ($categoryId) => [
                    $categoryId => ['entry_fee' => $categoryFees[$categoryId] ?? null],
                ])->all());
                $roundPrizes = $roundNumber === $rounds ? $championshipPrizes : $trophyPrizes;

                $this->syncEventExtras($event, $roundPrizes, $startingOrders, $categoryIds, $publishStartingOrder);
            }

            return redirect()->route('eventos.index')->with('success', 'Campeonato creado correctamente.');
        });
    }

    public function edit(Evento $event): View    {
        return view('eventos.edit', [
            'event' => $event->load(['category', 'categories', 'circuit', 'prizes', 'startingOrders']),
            'categories' => Categoria::orderBy('name')->get(),
            'circuits' => Circuito::where('organizer_id', Auth::id())->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Evento $event): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categorias,id'],
            'category_fees' => ['nullable', 'array'],
            'category_fees.*' => ['nullable', 'numeric', 'min:0'],
            'event_date' => ['required', 'date'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'location' => ['required', 'string', 'max:255'],
            'circuit_id' => ['nullable', 'integer', Rule::exists('circuitos', 'id')->where('organizer_id', Auth::id())],
            'organizer_whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\s-]+$/'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'entry_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_qr' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'event_background' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:programado,en_curso,finalizado,cancelado'],
            'prizes' => ['nullable', 'array'],
            'prizes.*.category_id' => ['required', 'integer', 'exists:categorias,id'],
            'prizes.*.position' => ['required', 'integer', 'in:1,2,3'],
            'prizes.*.award_type' => ['required', 'string', 'max:100'],
            'prizes.*.description' => ['nullable', 'string', 'max:2000'],
            'prizes.*.amount' => ['nullable', 'numeric', 'min:0'],
            'starting_orders' => ['nullable', 'array'],
            'starting_orders.*.category_id' => ['required', 'integer', 'exists:categorias,id'],
            'starting_orders.*.position' => ['required', 'integer', 'min:1', 'max:100'],
            'starting_orders.*.scheduled_time' => ['nullable', 'date_format:H:i'],
            'publish_starting_order' => ['nullable', 'boolean'],
        ]);

        $wasPublished = $event->status === 'programado';
        $categoryIds = $this->normalizeCategoryIds($validated['category_ids'] ?? []);
        $categoryFees = $this->normalizeCategoryFees($validated['category_fees'] ?? []);
        unset($validated['category_ids']);
        unset($validated['category_fees']);
        $prizes = $this->normalizePrizeRows($validated['prizes'] ?? []);
        $startingOrders = $this->normalizeStartingOrderRows($validated['starting_orders'] ?? []);
        $publishStartingOrder = filter_var($validated['publish_starting_order'] ?? false, FILTER_VALIDATE_BOOLEAN);
        unset($validated['prizes'], $validated['starting_orders'], $validated['publish_starting_order']);

        if (empty($categoryIds)) {
            throw ValidationException::withMessages([
                'category_ids' => 'Debe seleccionar al menos una categoría válida.',
            ]);
        }

        if ($request->hasFile('payment_qr')) {
            if ($event->payment_qr_path) {
                Storage::disk('public')->delete($event->payment_qr_path);
            }

            $validated['payment_qr_path'] = $request->file('payment_qr')->store('payment-qrs', 'public');
        }

        if ($request->hasFile('event_background')) {
            if ($event->event_background_path) {
                Storage::disk('public')->delete($event->event_background_path);
            }

            $validated['event_background_path'] = $request->file('event_background')->store('event-backgrounds', 'public');
        }

        $event->update($validated);
        $event->categories()->sync(collect($categoryIds)->mapWithKeys(fn ($categoryId) => [
            $categoryId => ['entry_fee' => $categoryFees[$categoryId] ?? $event->entry_fee],
        ])->all());
        $this->syncEventExtras($event, $prizes, $startingOrders, $categoryIds, $publishStartingOrder);

        if (!$wasPublished && $event->status === 'programado') {
            User::whereIn('role', ['usuario', 'piloto'])
                ->get()
                ->each(fn (User $user) => $user->notify(new EventoPublicado($event->id, $event->title)));
        }

        return redirect()->route('eventos.index')->with('success', 'Evento actualizado correctamente.');
    }

    public function destroy(Evento $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('eventos.index')->with('success', 'Evento eliminado correctamente.');
    }

    public function myEvents(): View
    {
        $participations = Inscripcion::with(['event', 'participant'])
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

    public function results(Evento $event): View
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

        $registrations = Inscripcion::with('participant')
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

    public function ranking(Evento $event): View
    {
        if ((! Auth::check() || Auth::user()->role === 'usuario') && ! $event->results_published) {
            abort(404);
        }

        $event->syncStatusByDate();

        $registrations = Inscripcion::with('participant')
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
        Evento::query()
            ->whereIn('status', ['programado', 'en_curso'])
            ->get()
            ->each(fn (Evento $event) => $event->syncStatusByDate());

        $createdEvents = Evento::withCount([
            'registrations as active_registrations_count' => fn ($query) => $query->active(),
            'registrations as confirmed_registrations_count' => fn ($query) => $query->where('status', 'confirmada'),
        ])->latest('created_at')->get();

        $upcomingEvents = $createdEvents->where('status', 'programado');
        $activeEvents = $createdEvents->where('status', 'en_curso');
        $finishedEvents = $createdEvents->where('status', 'finalizado');

        return view('organizador.mis-eventos', compact('upcomingEvents', 'activeEvents', 'finishedEvents'));
    }

    private function syncEventExtras(Evento $event, array $prizes, array $startingOrders, array $categoryIds, bool $publishStartingOrder): void
    {
        $allowedCategoryIds = collect($categoryIds)->map(fn ($categoryId) => (int) $categoryId)->values();

        $this->ensureUniquePositions($prizes, 'prizes', false);
        $this->ensureCompletePrizePodium($prizes, $allowedCategoryIds);
        $this->ensureUniquePositions($startingOrders, 'starting_orders', true);
        $this->ensureSequentialPositions($startingOrders);

        foreach ([$prizes, $startingOrders] as $rows) {
            foreach ($rows as $row) {
                if (! $allowedCategoryIds->contains((int) ($row['category_id'] ?? 0))) {
                    throw ValidationException::withMessages([
                        'category_ids' => 'Cada premio y largada debe usar una categoría asociada al evento.',
                    ]);
                }
            }
        }

        $event->prizes()->delete();
        foreach ($prizes as $prize) {
            $event->prizes()->create([
                'category_id' => $prize['category_id'],
                'position' => $prize['position'],
                'award_type' => $prize['award_type'],
                'description' => $prize['description'] ?? null,
                'amount' => $prize['amount'] ?? null,
            ]);
        }

        $event->startingOrders()->delete();
        foreach ($startingOrders as $startingOrder) {
            $event->startingOrders()->create([
                'category_id' => $startingOrder['category_id'],
                'position' => $startingOrder['position'],
                'scheduled_time' => $startingOrder['scheduled_time'] ?? null,
                'is_published' => $publishStartingOrder,
            ]);
        }
    }

    private function normalizeCategoryIds(array $categoryIds): array
    {
        return collect($categoryIds)
            ->map(fn ($categoryId) => is_numeric($categoryId) ? (int) $categoryId : null)
            ->filter(fn ($categoryId) => $categoryId !== null)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeChampionshipScoring(array $input, array $categoryIds, int $rounds): array
    {
        $scoring = [];
        foreach ($categoryIds as $categoryId) {
            foreach ([1, 2, 3] as $position) {
                for ($round = 1; $round <= $rounds; $round++) {
                    $points = $input[$categoryId][$position][$round] ?? null;
                    if (! is_numeric($points) || (int) $points < 0 || (int) $points > 9999) {
                        throw ValidationException::withMessages([
                            "championship_points.{$categoryId}.{$position}.{$round}" => 'Completa los puntos de cada puesto y fecha.',
                        ]);
                    }

                    $scoring[] = [
                        'category_id' => $categoryId,
                        'round_number' => $round,
                        'position' => $position,
                        'points' => (int) $points,
                    ];
                }
            }
        }

        return $scoring;
    }

    private function normalizeChampionshipPrizes(array $input, array $categoryIds): array
    {
        $prizes = [];
        foreach ($categoryIds as $categoryId) {
            foreach ([1, 2, 3] as $position) {
                $amount = $input[$categoryId][$position]['amount'] ?? null;
                $prizes[] = [
                    'category_id' => $categoryId,
                    'position' => $position,
                    'award_type' => 'Premio final y trofeo',
                    'description' => null,
                    'amount' => is_numeric($amount) ? (float) $amount : null,
                ];
            }
        }

        return $prizes;
    }

    private function normalizeCategoryFees(array $categoryFees): array
    {
        if (empty($categoryFees)) {
            return [];
        }

        return collect($categoryFees)
            ->mapWithKeys(function ($fee, $key) {
                $normalizedKey = is_numeric($key) ? (int) $key : null;
                $normalizedFee = is_numeric($fee) ? (float) $fee : null;

                if ($normalizedKey === null || $normalizedFee === null) {
                    return [];
                }

                return [$normalizedKey => $normalizedFee];
            })
            ->all();
    }

    private function normalizePrizeRows(array $prizes): array
    {
        if (empty($prizes)) {
            return [];
        }

        return collect($prizes)
            ->filter(fn ($prize) => is_array($prize))
            ->map(function ($prize) {
                if (! isset($prize['category_id'], $prize['position'])) {
                    return null;
                }

                $categoryId = is_numeric($prize['category_id']) ? (int) $prize['category_id'] : null;
                $position = is_numeric($prize['position']) ? (int) $prize['position'] : null;
                if ($categoryId === null || $position === null) {
                    return null;
                }

                return [
                    'category_id' => $categoryId,
                    'position' => $position,
                    'award_type' => (string) ($prize['award_type'] ?? ''),
                    'description' => $prize['description'] ?? null,
                    'amount' => is_numeric($prize['amount'] ?? null) ? (float) $prize['amount'] : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeStartingOrderRows(array $startingOrders): array
    {
        if (empty($startingOrders)) {
            return [];
        }

        return collect($startingOrders)
            ->filter(fn ($startingOrder) => is_array($startingOrder))
            ->map(function ($startingOrder) {
                if (! isset($startingOrder['category_id'], $startingOrder['position'])) {
                    return null;
                }

                $categoryId = is_numeric($startingOrder['category_id']) ? (int) $startingOrder['category_id'] : null;
                $position = is_numeric($startingOrder['position']) ? (int) $startingOrder['position'] : null;
                if ($categoryId === null || $position === null) {
                    return null;
                }

                return [
                    'category_id' => $categoryId,
                    'position' => $position,
                    'scheduled_time' => $startingOrder['scheduled_time'] ?? null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function ensureUniquePositions(array $rows, string $field, bool $global): void
    {
        $positions = collect($rows)->map(function (array $row) use ($global) {
            return $global
                ? (int) $row['position']
                : ((int) $row['category_id']).':'.((int) $row['position']);
        });

        if ($positions->count() !== $positions->unique()->count()) {
            throw ValidationException::withMessages([
                $field => $global
                    ? 'No puedes repetir la posición de una categoría en el orden de largada.'
                    : 'No puedes repetir el puesto de premio dentro de una categoría.',
            ]);
        }
    }

    private function ensureSequentialPositions(array $rows): void
    {
        $positions = collect($rows)->pluck('position')->map(fn ($position) => (int) $position)->sort()->values();

        foreach ($positions as $index => $position) {
            if ($position !== $index + 1) {
                throw ValidationException::withMessages([
                    'starting_orders' => 'El orden de largada debe comenzar en 1 y continuar sin saltos.',
                ]);
            }
        }
    }

    private function ensureCompletePrizePodium(array $prizes, $categoryIds): void
    {
        if (empty($prizes)) {
            return;
        }

        $positionsByCategory = collect($prizes)->groupBy(fn (array $prize) => (int) $prize['category_id'])
            ->map(fn ($rows) => $rows->pluck('position')->map(fn ($position) => (int) $position)->sort()->values()->all());

        foreach ($categoryIds as $categoryId) {
            if (($positionsByCategory->get($categoryId) ?? []) !== [1, 2, 3]) {
                throw ValidationException::withMessages([
                    'prizes' => 'Cada categoría con premios debe tener definidos el 1.º, 2.º y 3.º lugar.',
                ]);
            }
        }
    }
}
