<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Campeonato;
use App\Models\Inscripcion;
use App\Models\PuntuacionCampeonato;
use App\Models\User;
use App\Notifications\ResultadoPublicado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResultadoController extends Controller
{
    public function index(): View
    {
        $isManagementView = Auth::check() && in_array(Auth::user()->role, ['organizador', 'administrador'], true);

        $events = Evento::with([
            'category',
            'categories',
            'championship',
            'championship.scoringRules',
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

        $championshipStandings = $this->championshipStandings($events, $isManagementView);

        return view('resultados.index', compact('events', 'championshipStandings'));
    }

    public function update(Request $request, Inscripcion $registration): RedirectResponse
    {
        $validated = $request->validate([
            'result_position' => ['nullable', 'integer', 'min:1'],
            'result_points' => ['nullable', 'integer', 'min:0'],
        ]);

        $event = $registration->event;
        if ($event->championship_id !== null) {
            $position = $validated['result_position'] ?? null;
            $points = $position === null
                ? null
                : PuntuacionCampeonato::query()
                    ->where('championship_id', $event->championship_id)
                    ->where('category_id', $registration->category_id)
                    ->where('round_number', $event->round_number)
                    ->where('position', $position)
                    ->value('points');

            $validated['result_points'] = $position === null
                ? null
                : ($points === null ? $this->pointsForChampionshipPosition($position) : (int) $points);
        }

        $registration->update($validated);
        $event->update(['results_published' => true]);

        User::whereIn('role', ['usuario', 'piloto'])
            ->get()
            ->each(fn (User $user) => $user->notify(new ResultadoPublicado($event->id, $event->title)));

        return redirect()->route('resultados.index')->with('success', 'Resultado actualizado correctamente.');
    }

    public function destroy(Inscripcion $registration): RedirectResponse
    {
        $event = $registration->event;
        $participantName = $registration->participant->name ?? 'Participante';

        $registration->update([
            'result_position' => null,
            'result_points' => null,
        ]);

        return redirect()->route('resultados.index')->with('success', "Resultado de {$participantName} eliminado correctamente.");
    }

    private function pointsForChampionshipPosition(int $position): int
    {
        $topFive = [25, 22, 20, 18, 16];

        return $position <= count($topFive)
            ? $topFive[$position - 1]
            : max(0, 21 - $position);
    }

    private function championshipStandings(Collection $events, bool $isManagementView): array
    {
        $championshipIds = $events->pluck('championship_id')->filter()->unique()->values();
        if ($championshipIds->isEmpty()) {
            return [];
        }

        return Campeonato::query()
            ->whereIn('id', $championshipIds)
            ->with([
                'events' => fn ($query) => $query->orderBy('round_number'),
                'events.registrations' => fn ($query) => $query
                    ->where('status', 'confirmada')
                    ->with(['participant', 'category']),
                'events.prizes',
            ])
            ->get()
            ->mapWithKeys(function (Campeonato $championship) use ($isManagementView) {
                $rounds = $championship->events;
                $finalRound = $rounds->firstWhere('round_number', $championship->total_rounds);
                $isComplete = $rounds->count() === $championship->total_rounds
                    && $rounds->every(function (Evento $round) {
                        $registrations = $round->registrations->where('status', 'confirmada');

                        return $round->status === 'finalizado'
                            && $round->results_published
                            && $registrations->every(fn (Inscripcion $registration) => $registration->result_position !== null);
                    });

                $scoreRows = $rounds->flatMap(function (Evento $round) {
                    return $round->registrations
                        ->where('status', 'confirmada')
                        ->filter(fn (Inscripcion $registration) => $registration->result_position !== null)
                        ->map(fn (Inscripcion $registration) => [
                            'participant_id' => $registration->participant_id,
                            'participant' => $registration->participant?->name ?? 'Piloto',
                            'category_id' => $registration->category_id,
                            'category' => $registration->category?->name ?? 'General',
                            'round_number' => $round->round_number,
                            'position' => (int) $registration->result_position,
                            'points' => (int) ($registration->result_points ?? 0),
                        ]);
                });

                $categories = $scoreRows->groupBy(fn (array $row) => $row['category_id'] ?? 'general')
                    ->map(function (Collection $categoryRows) use ($finalRound) {
                        $categoryName = $categoryRows->first()['category'];
                        $standings = $categoryRows
                            ->groupBy('participant_id')
                            ->map(function (Collection $pilotRows) use ($finalRound) {
                                $lastRoundResult = $finalRound
                                    ? $pilotRows->firstWhere('round_number', $finalRound->round_number)
                                    : null;

                                return [
                                    'participant' => $pilotRows->last()['participant'],
                                    'points' => $pilotRows->sum('points'),
                                    'rounds_scored' => $pilotRows->count(),
                                    'wins' => $pilotRows->where('position', 1)->count(),
                                    'final_round_position' => $lastRoundResult['position'] ?? null,
                                ];
                            })
                            ->sort(function (array $left, array $right) {
                                return ($right['points'] <=> $left['points'])
                                    ?: ($right['wins'] <=> $left['wins'])
                                    ?: (($left['final_round_position'] ?? PHP_INT_MAX) <=> ($right['final_round_position'] ?? PHP_INT_MAX))
                                    ?: strcasecmp($left['participant'], $right['participant']);
                            })
                            ->values();

                        $previousTieKey = null;
                        $rank = 0;

                        return $standings->map(function (array $row, int $index) use (&$previousTieKey, &$rank, $categoryName) {
                            $tieKey = implode(':', [
                                $row['points'],
                                $row['wins'],
                                $row['final_round_position'] ?? 'none',
                            ]);
                            if ($tieKey !== $previousTieKey) {
                                $rank = $index + 1;
                                $previousTieKey = $tieKey;
                            }

                            $row['rank'] = $rank;
                            $row['is_champion'] = $rank === 1;
                            $row['category'] = $categoryName;

                            return $row;
                        });
                    });

                $finalPrizes = $finalRound?->prizes
                    ->groupBy('category_id')
                    ->map(fn (Collection $prizes) => $prizes->keyBy('position')) ?? collect();

                return [$championship->id => [
                    'championship' => $championship,
                    'categories' => $categories,
                    'final_prizes' => $finalPrizes,
                    'complete' => $isComplete,
                    'management' => $isManagementView,
                ]];
            })
            ->all();
    }
}