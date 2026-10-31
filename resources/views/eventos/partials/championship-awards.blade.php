<div class="workspace-championship-awards" id="championship-awards">
    <div class="workspace-championship-awards-heading">
        <div class="workspace-championship-awards-icon" aria-hidden="true">🏆</div>
        <div>
            <h3>Puntos y premios por categoría</h3>
            <p>Configura los puntos de cada fecha y el premio final para cada puesto.</p>
        </div>
        <div class="workspace-championship-round-count">
            <span>Total de fechas</span>
            <strong id="championship-round-count">{{ old('rounds', 6) }}</strong>
        </div>
    </div>

    <div class="workspace-round-legend" id="championship-round-legend">
        @for($round = 1; $round <= 12; $round++)
            <div class="workspace-round-legend-item {{ $round === (int) old('rounds', 6) ? 'is-final' : '' }}" data-round="{{ $round }}">
                <strong>Fecha {{ $round }}</strong>
                <span>{{ $round === (int) old('rounds', 6) ? 'Puntos + premio final' : 'Puntos + trofeo' }}</span>
            </div>
        @endfor
    </div>

    <div class="workspace-championship-categories" id="championship-award-categories">
        @forelse($categories as $category)
            @php
                $categoryIsSelected = in_array($category->id, old('category_ids', []));
                $categoryPoints = old('championship_points.' . $category->id, []);
                $categoryPrizes = old('championship_prizes.' . $category->id, []);
            @endphp
            <section class="workspace-championship-category {{ $categoryIsSelected ? '' : 'd-none' }}" data-category-id="{{ $category->id }}">
                <h4>{{ $category->name }}</h4>
                <div class="workspace-championship-table-wrap">
                    <table class="workspace-championship-table">
                        <thead>
                            <tr>
                                <th scope="col">Puesto</th>
                                @for($round = 1; $round <= 12; $round++)
                                    <th scope="col" data-round="{{ $round }}">
                                        <span>F.{{ $round }}</span>
                                        <small>{{ $round === (int) old('rounds', 6) ? 'Puntos + premio' : 'Puntos + trofeo' }}</small>
                                    </th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach([1 => '1.er lugar', 2 => '2.º lugar', 3 => '3.er lugar'] as $position => $label)
                                <tr>
                                    <th scope="row">{{ $label }}</th>
                                    @for($round = 1; $round <= 12; $round++)
                                        @php
                                            $defaultPoints = [1 => 25, 2 => 22, 3 => 20, 4 => 18, 5 => 16][$position] ?? max(0, 21 - $position);
                                        @endphp
                                        <td data-round="{{ $round }}">
                                            <label class="visually-hidden" for="championship-points-{{ $category->id }}-{{ $position }}-{{ $round }}">Puntos, {{ $category->name }}, {{ $label }}, fecha {{ $round }}</label>
                                            <div class="workspace-award-input">
                                                <input id="championship-points-{{ $category->id }}-{{ $position }}-{{ $round }}" type="number" min="0" max="9999" name="championship_points[{{ $category->id }}][{{ $position }}][{{ $round }}]" value="{{ data_get($categoryPoints, $position . '.' . $round, $defaultPoints) }}" aria-label="Puntos en fecha {{ $round }}">
                                                <span>pts</span><span class="workspace-award-trophy" aria-label="Trofeo">🏆</span>
                                            </div>
                                            <div class="workspace-final-prize-slot" data-position="{{ $position }}" id="championship-prize-slot-{{ $category->id }}-{{ $position }}-{{ $round }}">
                                                @if($round === (int) old('rounds', 6))
                                                    <div class="workspace-final-prize-control">
                                                        <label class="workspace-award-money-label" for="championship-prize-{{ $category->id }}-{{ $position }}">Premio final (Bs.)</label>
                                                        <div class="workspace-award-money">
                                                            <span>Bs.</span>
                                                            <input id="championship-prize-{{ $category->id }}-{{ $position }}" type="number" min="0" step="0.01" name="championship_prizes[{{ $category->id }}][{{ $position }}][amount]" value="{{ data_get($categoryPrizes, $position . '.amount') }}" placeholder="Monto final">
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <p class="workspace-championship-empty">Crea categorías antes de configurar los premios.</p>
        @endforelse
        <p class="workspace-championship-empty {{ old('category_ids') ? 'd-none' : '' }}" id="championship-awards-empty">Selecciona una categoría para configurar sus puntos y premios.</p>
    </div>

    <div class="workspace-championship-note">
        <span aria-hidden="true">i</span>
        <div>
            <strong>Así funciona el campeonato</strong>
            <p>En todas las fechas se suman los puntos y se entregan trofeos. El premio económico se reserva para el cierre y se asigna según la clasificación general acumulada.</p>
        </div>
    </div>
</div>
