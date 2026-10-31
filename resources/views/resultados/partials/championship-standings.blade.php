@php
    $championshipStanding = $championshipStandings[$event->championship_id] ?? null;
@endphp

@if($championshipStanding)
    <section class="card border-primary-subtle shadow-sm my-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <span class="text-uppercase small fw-semibold text-primary">Campeonato</span>
                    <h4 class="fw-bold mb-1">Clasificación general acumulada</h4>
                    <p class="text-muted small mb-0">{{ $championshipStanding['championship']->name }} · {{ $championshipStanding['championship']->total_rounds }} fechas</p>
                </div>
                <span class="badge {{ $championshipStanding['complete'] ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ $championshipStanding['complete'] ? 'Campeón definido' : 'Clasificación provisional' }}
                </span>
            </div>

            @unless($championshipStanding['complete'])
                <div class="alert alert-info py-2 small">
                    Los puntos se acumulan fecha por fecha. El premio mayor se entrega al líder cuando estén publicados los resultados de todas las fechas.
                </div>
            @else
                <p class="small text-muted">El premio mayor corresponde al primer lugar de esta clasificación. Los empates se resuelven por más victorias y luego por mejor puesto en la última fecha; si persisten, comparten el lugar.</p>
            @endunless

            @forelse($championshipStanding['categories'] as $categoryId => $rows)
                @php
                    $categoryPrizes = $championshipStanding['final_prizes']->get($categoryId, collect());
                @endphp
                <h5 class="fw-semibold mt-3">{{ $rows->first()['category'] }}</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-3">
                        <thead>
                            <tr><th>Puesto</th><th>Piloto</th><th>Fechas con resultado</th><th class="text-end">Puntos acumulados</th><th>Premio final</th></tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                @php
                                    $finalPrize = $categoryPrizes->get($row['rank']);
                                @endphp
                                <tr @class(['table-warning' => $championshipStanding['complete'] && $row['is_champion']])>
                                    <td>{{ $row['rank'] }}.º</td>
                                    <td>
                                        <strong>{{ $row['participant'] }}</strong>
                                        @if($championshipStanding['complete'] && $row['is_champion'])
                                            <span class="badge bg-warning text-dark ms-1">Campeón</span>
                                        @endif
                                    </td>
                                    <td>{{ $row['rounds_scored'] }} / {{ $championshipStanding['championship']->total_rounds }}</td>
                                    <td class="text-end fw-bold">{{ $row['points'] }}</td>
                                    <td>
                                        @if($championshipStanding['complete'] && $finalPrize)
                                            {{ $finalPrize->award_type }}
                                            @if($finalPrize->amount !== null)
                                                · Bs. {{ number_format((float) $finalPrize->amount, 2) }}
                                            @endif
                                        @elseif($championshipStanding['complete'] && $row['is_champion'])
                                            Premio mayor
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <p class="text-muted mb-0">Todavía no hay resultados cargados para este campeonato.</p>
            @endforelse
        </div>
    </section>
@endif
