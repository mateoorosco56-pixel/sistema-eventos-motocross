<?php

use App\Models\Evento;
use App\Models\Categoria;
use App\Models\Campeonato;
use App\Models\Participante;
use App\Models\Inscripcion;
use App\Models\PuntuacionCampeonato;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;

uses(WithFaker::class);

it('allows an organizer to create an event', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $category = Categoria::create(['name' => 'MX1']);

    $this->actingAs($user)
        ->post('/eventos', [
            'title' => 'Gran Premio AMC',
            'description' => 'Carrera de motocross regional.',
            'event_date' => '2026-09-15',
            'start_time' => '09:30',
            'location' => 'Cochabamba',
            'organizer_whatsapp' => '+591 700-12345',
            'status' => 'programado',
            'category_ids' => [$category->id],
            'category_fees' => [$category->id => '150'],
        ])
        ->assertRedirect('/eventos');

    $this->assertDatabaseHas('eventos', [
        'title' => 'Gran Premio AMC',
        'location' => 'Cochabamba',
        'start_time' => '09:30',
        'organizer_whatsapp' => '+591 700-12345',
        'status' => 'programado',
    ]);
    $this->assertDatabaseHas('categoria_evento', [
        'event_id' => Evento::where('title', 'Gran Premio AMC')->value('id'),
        'category_id' => $category->id,
        'entry_fee' => '150.00',
    ]);

    $event = Evento::where('title', 'Gran Premio AMC')->firstOrFail();
    $this->actingAs($user)
        ->get(route('eventos.show', $event))
        ->assertOk()
        ->assertSee('Costo de inscripción')
        ->assertSee('Bs. 150.00')
        ->assertSee('https://wa.me/59170012345', false)
        ->assertSee('WhatsApp del organizador');
});

it('allows an organizer to create a championship with six or seven rounds', function (int $rounds) {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $category = Categoria::create(['name' => 'MX1']);
    $championshipPoints = [];
    foreach ([1 => 25, 2 => 22, 3 => 20] as $position => $defaultPoints) {
        foreach (range(1, $rounds) as $round) {
            $championshipPoints[$category->id][$position][$round] = $round === 1
                ? [1 => 10, 2 => 7, 3 => 5][$position]
                : $defaultPoints;
        }
    }

    $this->actingAs($user)
        ->post('/eventos', [
            'title' => 'Copa AMC',
            'description' => 'Campeonato de seis fechas.',
            'event_date' => '2026-09-15',
            'start_time' => '09:30',
            'location' => 'Cochabamba',
            'status' => 'programado',
            'category_ids' => [$category->id],
            'category_fees' => [$category->id => '150'],
            'is_championship' => true,
            'championship_name' => 'Copa AMC 2026',
            'championship_start_date' => '2026-09-15',
            'rounds' => $rounds,
            'championship_interval_days' => 7,
            'championship_points' => $championshipPoints,
            'championship_prizes' => [
                $category->id => [
                    1 => ['amount' => '1000'],
                    2 => ['amount' => '700'],
                    3 => ['amount' => '400'],
                ],
            ],
        ])
        ->assertRedirect('/eventos');

    $this->assertDatabaseHas('campeonatos', [
        'name' => 'Copa AMC 2026',
        'status' => 'programado',
        'total_rounds' => $rounds,
    ]);

    $championship = \App\Models\Campeonato::where('name', 'Copa AMC 2026')->firstOrFail();
    $this->assertDatabaseHas('eventos', [
        'championship_id' => $championship->id,
        'round_number' => 1,
        'title' => 'Copa AMC 2026 - Fecha 1',
    ]);
    $this->assertSame(
        '2026-09-15',
        $championship->events()->where('round_number', 1)->firstOrFail()->event_date->toDateString()
    );
    $this->assertDatabaseHas('eventos', [
        'championship_id' => $championship->id,
        'round_number' => $rounds,
        'title' => 'Copa AMC 2026 - Fecha ' . $rounds,
    ]);
    $this->assertEquals($rounds, $championship->fresh()->events()->count());
    $this->assertDatabaseHas('puntuaciones_campeonato', [
        'championship_id' => $championship->id,
        'category_id' => $category->id,
        'round_number' => 1,
        'position' => 1,
        'points' => 10,
    ]);
    $this->assertDatabaseHas('puntuaciones_campeonato', [
        'championship_id' => $championship->id,
        'category_id' => $category->id,
        'round_number' => $rounds,
        'position' => 3,
        'points' => 20,
    ]);
    $this->assertDatabaseHas('premios_evento', [
        'event_id' => $championship->events()->where('round_number', 1)->value('id'),
        'category_id' => $category->id,
        'position' => 1,
        'award_type' => 'Trofeo',
        'amount' => null,
    ]);
    $this->assertDatabaseHas('premios_evento', [
        'event_id' => $championship->events()->where('round_number', $rounds)->value('id'),
        'category_id' => $category->id,
        'position' => 1,
        'award_type' => 'Premio final y trofeo',
        'amount' => '1000.00',
    ]);
})->with([6, 7]);

it('awards the final championship prize to the cumulative points leader', function () {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create(['role' => 'organizador']);
    $category = Categoria::create(['name' => 'MX1']);
    $championship = Campeonato::create([
        'name' => 'Copa AMC',
        'total_rounds' => 2,
        'organizer_id' => $organizer->id,
    ]);

    $events = collect([1, 2])->map(fn (int $round) => Evento::create([
        'title' => 'Copa AMC - Fecha ' . $round,
        'event_date' => now()->subDays(3 - $round)->toDateString(),
        'location' => 'Cochabamba',
        'status' => 'finalizado',
        'results_published' => false,
        'championship_id' => $championship->id,
        'round_number' => $round,
    ]));
    $events->each(fn (Evento $event) => $event->categories()->attach($category->id));
    foreach ([1 => 'Premio mayor', 2 => 'Trofeo', 3 => 'Trofeo'] as $position => $award) {
        $events->last()->prizes()->create([
            'category_id' => $category->id,
            'position' => $position,
            'award_type' => $award,
            'amount' => $position === 1 ? 1500 : null,
        ]);
    }

    $registrations = collect();
    foreach (['Ana', 'Beto', 'Caro'] as $name) {
        $participant = Participante::create([
            'name' => $name,
            'document' => 'DOC-CAMPEONATO-' . $name,
            'email' => strtolower($name) . '-campeonato@example.com',
        ]);

        foreach ($events as $event) {
            $registrations->push(Inscripcion::create([
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'category_id' => $category->id,
                'status' => 'confirmada',
            ]));
        }
    }

    $positionsByParticipantAndRound = [
        'Ana' => [1, 2],
        'Beto' => [3, 1],
        'Caro' => [2, 3],
    ];
    foreach ($registrations as $registration) {
        $participantName = $registration->participant->name;
        $roundNumber = $events->search(fn (Evento $event) => $event->id === $registration->event_id) + 1;
        $this->actingAs($organizer)
            ->patch(route('resultados.update', $registration), [
                'result_position' => $positionsByParticipantAndRound[$participantName][$roundNumber - 1],
            ])
            ->assertRedirect(route('resultados.index'));
    }

    $this->actingAs($organizer)
        ->get(route('resultados.index'))
        ->assertOk()
        ->assertSee('Clasificación general acumulada')
        ->assertSee('Campeón definido')
        ->assertSee('Ana')
        ->assertSee('Premio mayor')
        ->assertSee('1,500.00')
        ->assertSee('47')
        ->assertSee('45');
});

it('automatically assigns championship points from the selected position', function (int $position, int $points) {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create(['role' => 'organizador']);
    $championship = Campeonato::create([
        'name' => 'Copa de prueba',
        'total_rounds' => 6,
        'organizer_id' => $organizer->id,
    ]);
    $event = Evento::create([
        'title' => 'Copa de prueba - Fecha 1',
        'event_date' => now()->subDay()->toDateString(),
        'location' => 'Cochabamba',
        'status' => 'finalizado',
        'championship_id' => $championship->id,
        'round_number' => 1,
    ]);
    $registration = null;
    foreach (range(1, $position) as $number) {
        $participant = Participante::create([
            'name' => 'Piloto de campeonato ' . $number,
            'document' => 'DOC-PUNTOS-' . $position . '-' . $number,
            'email' => 'puntos-' . $position . '-' . $number . '@example.com',
        ]);
        $createdRegistration = Inscripcion::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'confirmada',
        ]);
        if ($number === $position) {
            $registration = $createdRegistration;
        }
    }

    if ($position === 21) {
        $this->actingAs($organizer)
            ->get(route('resultados.index'))
            ->assertOk()
            ->assertSee('Puntos automáticos por puesto')
            ->assertSee('readonly', false)
            ->assertSee('21.º');
    }

    $this->actingAs($organizer)
        ->patch(route('resultados.update', $registration), [
            'result_position' => $position,
            'result_points' => 999,
        ])
        ->assertRedirect(route('resultados.index'));

    $this->assertDatabaseHas('inscripciones', [
        'id' => $registration->id,
        'result_position' => $position,
        'result_points' => $points,
    ]);
})->with([
    'first place' => [1, 25],
    'second place' => [2, 22],
    'third place' => [3, 20],
    'fifth place' => [5, 16],
    'sixth place' => [6, 15],
    'twentieth place' => [20, 1],
    'twenty-first place' => [21, 0],
]);

it('uses the championship point value configured for that round, category and position', function () {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create(['role' => 'organizador']);
    $category = Categoria::create(['name' => 'MX1']);
    $championship = Campeonato::create([
        'name' => 'Campeonato con puntos personalizados',
        'total_rounds' => 6,
        'organizer_id' => $organizer->id,
    ]);
    PuntuacionCampeonato::create([
        'championship_id' => $championship->id,
        'category_id' => $category->id,
        'round_number' => 1,
        'position' => 1,
        'points' => 10,
    ]);
    $event = Evento::create([
        'title' => 'Fecha 1',
        'event_date' => now()->subDay()->toDateString(),
        'location' => 'Cochabamba',
        'status' => 'finalizado',
        'championship_id' => $championship->id,
        'round_number' => 1,
    ]);
    $participant = Participante::create([
        'name' => 'Piloto con puntaje especial',
        'document' => 'DOC-PUNTOS-PERSONALIZADOS',
        'email' => 'puntaje-personalizado@example.com',
    ]);
    $registration = Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'category_id' => $category->id,
        'status' => 'confirmada',
    ]);

    $this->actingAs($organizer)
        ->get(route('resultados.index'))
        ->assertOk()
        ->assertSee('data-points="10"', false);

    $this->actingAs($organizer)
        ->patch(route('resultados.update', $registration), [
            'result_position' => 1,
            'result_points' => 999,
        ])
        ->assertRedirect(route('resultados.index'));

    $this->assertDatabaseHas('inscripciones', [
        'id' => $registration->id,
        'result_position' => 1,
        'result_points' => 10,
    ]);
});

it('keeps manual points for a standalone event', function () {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create(['role' => 'organizador']);
    $event = Evento::create([
        'title' => 'Evento individual',
        'event_date' => now()->subDay()->toDateString(),
        'location' => 'Cochabamba',
        'status' => 'finalizado',
    ]);
    $participant = Participante::create([
        'name' => 'Piloto individual',
        'document' => 'DOC-PUNTOS-MANUAL',
        'email' => 'puntos-manual@example.com',
    ]);
    $registration = Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'confirmada',
    ]);

    $this->actingAs($organizer)
        ->patch(route('resultados.update', $registration), [
            'result_position' => 1,
            'result_points' => 99,
        ])
        ->assertRedirect(route('resultados.index'));

    $this->assertDatabaseHas('inscripciones', [
        'id' => $registration->id,
        'result_points' => 99,
    ]);
});

it('shows available categories when creating an event', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    Categoria::create(['name' => 'MX1']);

    $this->actingAs($user)
        ->get('/eventos/crear')
        ->assertOk()
        ->assertSee('Categorías disponibles')
        ->assertSee('MX1')
        ->assertSee('Crear evento o campeonato')
        ->assertSee('Crear un campeonato de varias fechas')
        ->assertSee('id="event-date"', false)
        ->assertSee('Puntos y premios por categoría')
        ->assertSee('Puntos + trofeo')
        ->assertSee('Premio final (Bs.)')
        ->assertSee('value="7"', false);
});

it('shows the event list to an authenticated organizer', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $event = Evento::create([
        'title' => 'Evento del organizador',
        'event_date' => now()->addDays(10)->toDateString(),
        'location' => 'Cochabamba',
        'status' => 'programado',
    ]);
    $activeParticipant = Participante::create([
        'name' => 'Piloto Pendiente',
        'document' => 'DOC-PENDIENTE-LISTA',
        'email' => 'pendiente-lista@example.com',
        'phone' => '70000010',
    ]);
    $rejectedParticipant = Participante::create([
        'name' => 'Piloto Rechazado',
        'document' => 'DOC-RECHAZADO-LISTA',
        'email' => 'rechazado-lista@example.com',
        'phone' => '70000011',
    ]);
    Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $activeParticipant->id,
        'status' => 'pendiente',
    ]);
    Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $rejectedParticipant->id,
        'status' => 'rechazada',
    ]);

    $this->actingAs($user)
        ->get('/eventos')
        ->assertOk()
        ->assertSee('Mis eventos')
        ->assertSee('Pilotos inscritos')
        ->assertSee('<span class="organizer-event-count">1</span>', false);
});

it('shows an event cover in the public event listing', function () {
    /** @var \Tests\TestCase $this */
    $event = Evento::create([
        'title' => 'Copa con portada',
        'event_date' => '2026-10-15',
        'location' => 'Cochabamba',
        'status' => 'programado',
        'event_background_path' => 'event-backgrounds/copa.jpg',
    ]);

    $this->get('/eventos')
        ->assertOk()
        ->assertSee('/storage/event-backgrounds/copa.jpg')
        ->assertSee('alt="Portada del evento Copa con portada"', false)
        ->assertSee(route('eventos.show', $event), false);
});

it('shows a numeric badge next to the navigation events link for pilots when there are new event notifications and clears it after viewing them', function () {
    /** @var \Tests\TestCase $this */
    $pilot = User::factory()->create(['role' => 'piloto']);
    $event = Evento::create([
        'title' => 'Evento con aviso',
        'description' => 'Prueba de notificación.',
        'event_date' => '2026-10-05',
        'location' => 'Sucre',
        'status' => 'programado',
    ]);

    $pilot->notify(new \App\Notifications\EventoPublicado($event->id, $event->title));

    $response = $this->actingAs($pilot)
        ->get('/eventos');

    $response->assertOk();
    $this->assertStringContainsString('Eventos', $response->getContent());
    $this->assertStringContainsString('badge bg-danger rounded-pill ms-1', $response->getContent());
    $this->assertStringContainsString('>1<', $response->getContent());

    $this->assertSame(0, $pilot->fresh()->unreadNotifications->where('type', \App\Notifications\EventoPublicado::class)->count());
});

it('allows an organizer to update an event', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $event = Evento::create([
        'title' => 'Gran Premio AMC',
        'description' => 'Carrera de motocross regional.',
        'event_date' => '2026-09-15',
        'location' => 'Cochabamba',
        'status' => 'programado',
    ]);

    $category = Categoria::create(['name' => 'MX2']);

    $this->actingAs($user)
        ->get(route('eventos.edit', $event))
        ->assertOk()
        ->assertSee('<details class="workspace-collapsible-prizes"', false)
        ->assertSee('Premios y trofeos por categoría')
        ->assertDontSee('<details class="workspace-collapsible-prizes" open', false);

    $this->actingAs($user)
        ->patch('/eventos/' . $event->id, [
            'title' => 'Gran Premio AMC 2026',
            'description' => 'Carrera revisada.',
            'event_date' => '2026-09-20',
            'start_time' => '10:45',
            'location' => 'Santa Cruz',
            'status' => 'finalizado',
            'category_ids' => [$category->id],
        ])
        ->assertRedirect('/eventos');

    $this->assertDatabaseHas('eventos', [
        'id' => $event->id,
        'title' => 'Gran Premio AMC 2026',
        'location' => 'Santa Cruz',
        'start_time' => '10:45',
        'status' => 'finalizado',
    ]);
});

it('allows an organizer to delete an event', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $event = Evento::create([
        'title' => 'Evento a eliminar',
        'description' => 'Debe desaparecer.',
        'event_date' => '2026-10-01',
        'location' => 'La Paz',
        'status' => 'programado',
    ]);

    $this->actingAs($user)
        ->delete('/eventos/' . $event->id)
        ->assertRedirect('/eventos');

    $this->assertDatabaseMissing('eventos', [
        'id' => $event->id,
    ]);
});
