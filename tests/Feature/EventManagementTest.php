<?php

use App\Models\Categoria;
use App\Models\Evento;
use App\Models\Inscripcion;
use App\Models\Participante;
use App\Models\User;
use App\Notifications\EventoPublicado;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;

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
        ->assertSee('MX1')
        ->assertSee('https://wa.me/59170012345', false)
        ->assertSee('WhatsApp del organizador');
});

it('publishes the cost of each event category in the public event list', function () {
    $mx1 = Categoria::create(['name' => 'MX1']);
    $mx2 = Categoria::create(['name' => 'MX2']);
    $event = Evento::create([
        'title' => 'Evento con precios por categoría',
        'event_date' => now()->addWeek()->toDateString(),
        'location' => 'Cochabamba',
        'status' => 'programado',
        'entry_fee' => 150,
    ]);
    $event->categories()->attach([
        $mx1->id => ['entry_fee' => 85],
        $mx2->id => ['entry_fee' => 120],
    ]);
    $viewer = User::factory()->create(['role' => 'piloto']);

    $this->actingAs($viewer)
        ->get(route('eventos.index'))
        ->assertOk()
        ->assertSee('Costo por categoría:')
        ->assertSee('MX1: Bs. 85.00')
        ->assertSee('MX2: Bs. 120.00')
        ->assertDontSee('MX1: Bs. 150.00');

    $regularUser = User::factory()->create(['role' => 'usuario']);
    $this->actingAs($regularUser)
        ->get(route('eventos.index'))
        ->assertOk()
        ->assertSee('MX1: Bs. 85.00')
        ->assertSee('MX2: Bs. 120.00');
});

it('emails user and pilot accounts when an organizer creates a published event', function () {
    /** @var \Tests\TestCase $this */
    Notification::fake();

    $organizer = User::factory()->create(['role' => 'organizador']);
    $user = User::factory()->create(['role' => 'usuario']);
    $pilot = User::factory()->create(['role' => 'piloto']);
    $category = Categoria::create(['name' => 'MX1']);

    $this->actingAs($organizer)
        ->post('/eventos', [
            'title' => 'Gran Premio AMC',
            'event_date' => '2026-09-15',
            'start_time' => '09:30',
            'location' => 'Cochabamba',
            'status' => 'programado',
            'category_ids' => [$category->id],
        ])
        ->assertRedirect('/eventos');

    $includesMailAndDatabase = fn ($notification, $channels) =>
        in_array('mail', $channels, true) && in_array('database', $channels, true);

    Notification::assertSentTo($user, EventoPublicado::class, $includesMailAndDatabase);
    Notification::assertSentTo($pilot, EventoPublicado::class, $includesMailAndDatabase);
    Notification::assertNotSentTo($organizer, EventoPublicado::class);
});

it('emails user and pilot accounts when an organizer publishes an existing event', function () {
    /** @var \Tests\TestCase $this */
    Notification::fake();

    $organizer = User::factory()->create(['role' => 'organizador']);
    $user = User::factory()->create(['role' => 'usuario']);
    $pilot = User::factory()->create(['role' => 'piloto']);
    $event = Evento::create([
        'title' => 'Gran Premio AMC',
        'event_date' => '2026-09-15',
        'location' => 'Cochabamba',
        'status' => 'cancelado',
    ]);
    $category = Categoria::create(['name' => 'MX1']);

    $this->actingAs($organizer)
        ->patch('/eventos/' . $event->id, [
            'title' => $event->title,
            'event_date' => '2026-09-15',
            'start_time' => '09:30',
            'location' => 'Cochabamba',
            'status' => 'programado',
            'category_ids' => [$category->id],
        ])
        ->assertRedirect('/eventos');

    $includesMailAndDatabase = fn ($notification, $channels) =>
        in_array('mail', $channels, true) && in_array('database', $channels, true);

    Notification::assertSentTo($user, EventoPublicado::class, $includesMailAndDatabase);
    Notification::assertSentTo($pilot, EventoPublicado::class, $includesMailAndDatabase);
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
        ->assertSee('MX1');
});

it('keeps the public navigation collapsed on small screens until opened', function () {
    /** @var \Tests\TestCase $this */
    $this->get('/eventos')
        ->assertOk()
        ->assertSee('class="collapse navbar-collapse" id="navbarAMC"', false)
        ->assertSee('data-bs-target="#navbarAMC"', false)
        ->assertSee('aria-expanded="false"', false);
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

    $pilot->notify(new EventoPublicado($event->id, $event->title));

    $response = $this->actingAs($pilot)
        ->get('/eventos');

    $response->assertOk();
    $this->assertStringContainsString('Eventos', $response->getContent());
    $this->assertStringContainsString('badge bg-danger rounded-pill ms-1', $response->getContent());
    $this->assertStringContainsString('>1<', $response->getContent());

    $this->assertSame(0, $pilot->fresh()->unreadNotifications->where('type', EventoPublicado::class)->count());
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
