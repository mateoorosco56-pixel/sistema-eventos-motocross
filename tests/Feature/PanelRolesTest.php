<?php

use App\Models\User;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\Inscripcion;

it('shows the administrator dashboard for admins', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'role' => 'administrador',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Panel de control')
        ->assertSee('Administración')
        ->assertSee('amc-main-navbar', false)
        ->assertSee('amc-admin-navbar', false)
        ->assertSee('Usuarios')
        ->assertDontSee('navbar-toggler', false);
});

it('shows a different dashboard for organizers', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'name' => 'Organizer User',
        'email' => 'organizer@example.com',
        'role' => 'organizador',
    ]);

    $event = Evento::create([
        'title' => 'Evento del organizador',
        'description' => 'Evento de prueba',
        'event_date' => '2026-09-20',
        'location' => 'Circuito Central',
        'status' => 'programado',
    ]);

    $participant = Participante::create([
        'name' => 'Piloto de prueba',
        'document' => '12345678',
        'email' => 'piloto.prueba@example.com',
    ]);

    Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'pendiente',
        'result_position' => 1,
    ]);

    $response = $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Tu campeonato, en marcha.')
        ->assertSee('class="workspace-sidebar"', false);

    expect(substr_count($response->getContent(), '<strong class="fs-2">1</strong>'))->toBe(4);
});

it('shows organizer event groups by status on the event list', function () {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create([
        'name' => 'Organizer Status User',
        'email' => 'organizerstatus@example.com',
        'role' => 'organizador',
    ]);

    Evento::create([
        'title' => 'Evento próximo',
        'description' => 'Próximo',
        'event_date' => '2026-10-10',
        'location' => 'La Paz',
        'status' => 'programado',
    ]);

    Evento::create([
        'title' => 'Evento en curso',
        'description' => 'En curso',
        'event_date' => '2026-10-12',
        'location' => 'Santa Cruz',
        'status' => 'en_curso',
    ]);

    Evento::create([
        'title' => 'Evento finalizado',
        'description' => 'Finalizado',
        'event_date' => '2026-10-15',
        'location' => 'Cochabamba',
        'status' => 'finalizado',
        'results_published' => false,
    ]);

    $this->actingAs($organizer)
        ->get('/eventos')
        ->assertOk()
        ->assertSee('Próximos')
        ->assertSee('En curso')
        ->assertSee('Finalizados');
});

it('renders the organizer event management summary and actions', function () {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create([
        'name' => 'Organizer Events Page User',
        'email' => 'organizer-events-page@example.com',
        'role' => 'organizador',
    ]);

    Evento::create([
        'title' => 'AMC Primavera',
        'event_date' => now()->addDays(12)->toDateString(),
        'location' => 'Circuito Central',
        'status' => 'programado',
    ]);

    $this->actingAs($organizer)
        ->get(route('organizador.mis-eventos'))
        ->assertOk()
        ->assertSee('class="workspace-sidebar"', false)
        ->assertSee('Total de eventos')
        ->assertSee('AMC Primavera')
        ->assertSee('Ver')
        ->assertSee('Editar');
});

it('auto updates event status based on the scheduled date', function () {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create([
        'name' => 'Organizer Date User',
        'email' => 'organizerdate@example.com',
        'role' => 'organizador',
    ]);

    $pastEvent = Evento::create([
        'title' => 'Evento vencido',
        'description' => 'Debe cerrarse automáticamente',
        'event_date' => now()->subDay()->toDateString(),
        'location' => 'La Paz',
        'status' => 'programado',
    ]);

    $this->actingAs($organizer)
        ->get('/eventos')
        ->assertOk();

    expect($pastEvent->fresh()->status)->toBe('finalizado');
});

it('shows a different dashboard for pilots', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'name' => 'Pilot User',
        'email' => 'pilot@example.com',
        'role' => 'piloto',
    ]);

    $event = Evento::create([
        'title' => 'Evento del piloto',
        'description' => 'Prueba',
        'event_date' => '2026-09-25',
        'location' => 'Cochabamba',
        'status' => 'programado',
    ]);

    $participant = Participante::create([
        'name' => 'Piloto activo',
        'document' => '11111111',
        'email' => 'activo@example.com',
    ]);

    Inscripcion::create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'participant_id' => $participant->id,
        'status' => 'confirmada',
        'result_position' => 1,
        'result_points' => 20,
    ]);

    $otherPilot = User::factory()->create([
        'name' => 'Another Pilot',
        'email' => 'another-pilot@example.com',
        'role' => 'piloto',
    ]);
    $otherEvent = Evento::create([
        'title' => 'Evento privado de otro piloto',
        'event_date' => '2026-10-10',
        'location' => 'La Paz',
        'status' => 'programado',
    ]);
    $otherParticipant = Participante::create([
        'name' => 'Otro piloto',
        'document' => '22222222',
        'email' => 'otro@example.com',
    ]);
    Inscripcion::create([
        'event_id' => $otherEvent->id,
        'user_id' => $otherPilot->id,
        'participant_id' => $otherParticipant->id,
        'status' => 'pendiente',
    ]);

    $response = $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Cerrar sesión')
        ->assertSee('action="' . route('logout') . '"', false)
        ->assertDontSee('<details class="pilot-mobile-menu pilot-mobile-notifications">', false)
        ->assertSee('Panel del piloto')
        ->assertSee('Mis inscripciones')
        ->assertSee('Activas')
        ->assertSee('Historial')
        ->assertSee('Notificaciones')
        ->assertSee('Notificaciones recientes')
            ->assertSee('Ver todas')
        ->assertSee('Evento del piloto')
        ->assertSee('Eventos disponibles')
        ->assertSee('href="http://localhost/eventos"', false)
        ->assertSee('Ver inscripción');

    expect(substr_count($response->getContent(), 'class="pilot-registration-card"'))->toBe(1);
    $response->assertSee('1');
    $response->assertSee('Próximos eventos');
    $response->assertSee('Inscripciones');
    $response->assertSee('Resultados');
});

it('shows a different dashboard for regular users', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'name' => 'Regular User',
        'email' => 'user@example.com',
        'role' => 'usuario',
    ]);

    \App\Models\Evento::create([
        'title' => 'Evento publicado',
        'description' => 'Evento disponible para usuarios',
        'event_date' => '2026-09-20',
        'location' => 'Circuito Central',
        'status' => 'programado',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Portal del usuario')
        ->assertSee('<h3 class="fw-bold mb-1">1</h3>', false)
        ->assertSee('Ver eventos')
        ->assertSee('Ver resultados')
        ->assertSee('/eventos', false)
        ->assertSee('/resultados', false);
});

it('allows regular users to view public event and category pages', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'name' => 'Public User',
        'email' => 'public@example.com',
        'role' => 'usuario',
    ]);

    \App\Models\Evento::create([
        'title' => 'Evento visible para usuarios',
        'description' => 'Evento publicado por un organizador',
        'event_date' => '2026-09-25',
        'location' => 'Circuito Norte',
        'status' => 'programado',
    ]);

    $this->actingAs($user)
        ->get('/eventos')
        ->assertOk()
        ->assertSee('Ver a fondo')
        ->assertDontSee('+ Nuevo evento')
        ->assertDontSee('Editar')
        ->assertDontSee('Eliminar');

    $this->actingAs($user)
        ->get('/categorias')
        ->assertOk();
});

it('hides management navigation items from user and pilot roles', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'name' => 'Regular User',
        'email' => 'usernav@example.com',
        'role' => 'usuario',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Participantes')
        ->assertDontSee('Inscripciones');

    $pilot = User::factory()->create([
        'name' => 'Pilot User',
        'email' => 'pilotnav@example.com',
        'role' => 'piloto',
    ]);

    $this->actingAs($pilot)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Participantes')
        ->assertDontSee('Categorías');
});

it('hides event creation and event management from pilots', function () {
    /** @var \Tests\TestCase $this */
    $pilot = User::factory()->create([
        'name' => 'Pilot Evento User',
        'email' => 'pilotevent@example.com',
        'role' => 'piloto',
    ]);

    $this->actingAs($pilot)
        ->get('/eventos')
        ->assertOk()
        ->assertDontSee('+ Nuevo evento')
        ->assertDontSee('Editar')
        ->assertDontSee('Eliminar');

    $this->actingAs($pilot)
        ->get('/eventos/crear')
        ->assertForbidden();
});

it('allows pilots to view their participated events, results and ranking for seven-stage events', function () {
    /** @var \Tests\TestCase $this */
    $pilot = User::factory()->create([
        'name' => 'Pilot Evento User',
        'email' => 'pilotresults@example.com',
        'role' => 'piloto',
    ]);

    $event = \App\Models\Evento::create([
        'title' => 'MotoGP 7 fechas',
        'description' => 'Competencia con 7 fechas de campeonato',
        'event_date' => '2026-09-10',
        'location' => 'Circuito Norte',
        'status' => 'programado',
    ]);

    \App\Models\Participante::create([
        'name' => 'Pilot A',
        'document' => '12345678',
        'email' => 'pilot_a@example.com',
        'phone' => '999999999',
        'license' => 'ABC123',
        'team' => 'Team One',
    ]);

    \App\Models\Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => 1,
        'status' => 'confirmada',
    ]);

    $this->actingAs($pilot)
        ->get('/mis-eventos')
        ->assertOk()
        ->assertSee('Mis eventos');

    $this->actingAs($pilot)
        ->get('/eventos/' . $event->id . '/resultados')
        ->assertOk()
        ->assertSee('Resultados del evento');

    $this->actingAs($pilot)
        ->get('/eventos/' . $event->id . '/ranking')
        ->assertOk()
        ->assertSee('Ranking');
});

it('shows finalized events with pending results for pilots', function () {
    /** @var \Tests\TestCase $this */
    $pilot = User::factory()->create([
        'name' => 'Pilot Final User',
        'email' => 'pilotfinal@example.com',
        'role' => 'piloto',
    ]);

    $event = Evento::create([
        'title' => 'Rally La Paz 2026',
        'description' => 'Evento finalizado pendiente de resultados',
        'event_date' => '2026-08-30',
        'location' => 'La Paz',
        'status' => 'finalizado',
        'results_published' => false,
    ]);

    $participant = Participante::create([
        'name' => 'Juan Pérez',
        'document' => '22222222',
        'email' => 'juan.perez@example.com',
        'phone' => '777777777',
        'license' => 'XYZ123',
        'team' => 'Team Alpha',
    ]);

    Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'confirmada',
        'participated' => true,
    ]);

    $this->actingAs($pilot)
        ->get('/mis-eventos')
        ->assertOk()
        ->assertSee('Finalizados')
        ->assertSee('EVENTO FINALIZADO')
        ->assertSee('Los resultados todavía no han sido publicados');
});

it('shows pending results for organizers on finalized events', function () {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create([
        'name' => 'Organizer Results User',
        'email' => 'organizerresults@example.com',
        'role' => 'organizador',
    ]);

    $event = Evento::create([
        'title' => 'Rally La Paz 2026',
        'description' => 'Evento finalizado con resultados pendientes',
        'event_date' => '2026-08-30',
        'location' => 'La Paz',
        'status' => 'finalizado',
        'results_published' => false,
    ]);

    $participant = Participante::create([
        'name' => 'Carlos Gómez',
        'document' => '33333333',
        'email' => 'carlos.gomez@example.com',
        'phone' => '666666666',
        'license' => 'LMN456',
        'team' => 'Team Beta',
    ]);

    Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'confirmada',
        'participated' => true,
    ]);

    $this->actingAs($organizer)
        ->get('/resultados')
        ->assertOk()
        ->assertSee('RESULTADOS PENDIENTES')
        ->assertSee('El evento ha finalizado, pero todavía no has publicado los resultados.');
});

it('shows a summary for finalized events in the organizer detail view', function () {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create([
        'name' => 'Organizer Summary User',
        'email' => 'organizersummary@example.com',
        'role' => 'organizador',
    ]);

    $event = Evento::create([
        'title' => 'Rally Oruro 2026',
        'description' => 'Evento finalizado con resumen pendiente',
        'event_date' => '2026-09-05',
        'location' => 'Oruro',
        'status' => 'finalizado',
        'results_published' => false,
    ]);

    $participantA = Participante::create([
        'name' => 'Ana García',
        'document' => '44444444',
        'email' => 'ana.garcia@example.com',
        'phone' => '888888888',
        'license' => 'QWE789',
        'team' => 'Team One',
    ]);

    $participantB = Participante::create([
        'name' => 'Luis Ortega',
        'document' => '55555555',
        'email' => 'luis.ortega@example.com',
        'phone' => '999999999',
        'license' => 'RTY456',
        'team' => 'Team Two',
    ]);

    Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participantA->id,
        'status' => 'confirmada',
        'participated' => true,
    ]);

    Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participantB->id,
        'status' => 'confirmada',
        'participated' => false,
    ]);

    Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => Participante::create([
            'name' => 'Marta Díaz',
            'document' => '66666666',
            'email' => 'marta.diaz@example.com',
            'phone' => '111111111',
            'license' => 'UIO321',
            'team' => 'Team Three',
        ])->id,
        'status' => 'pendiente',
        'participated' => false,
    ]);

    $this->actingAs($organizer)
        ->get('/eventos/' . $event->id)
        ->assertOk()
        ->assertSee('Resumen del evento')
        ->assertSee('Inscritos')
        ->assertSee('Participaron')
        ->assertSee('RESULTADOS PENDIENTES');
});

it('shows only participating pilots in the event ranking', function () {
    /** @var \Tests\TestCase $this */
    $pilot = User::factory()->create([
        'name' => 'Pilot Ranking User',
        'email' => 'pilotranking@example.com',
        'role' => 'piloto',
    ]);

    $event = Evento::create([
        'title' => 'Ranking final del evento',
        'description' => 'Clasificación real de participantes',
        'event_date' => '2026-09-12',
        'location' => 'Potosí',
        'status' => 'finalizado',
        'results_published' => true,
    ]);

    $participantA = Participante::create([
        'name' => 'Alberto Ramos',
        'document' => '77777777',
        'email' => 'alberto.ramos@example.com',
        'phone' => '222222222',
        'license' => 'ZXC987',
        'team' => 'Team Alpha',
    ]);

    $participantB = Participante::create([
        'name' => 'Bruno Torres',
        'document' => '88888888',
        'email' => 'bruno.torres@example.com',
        'phone' => '333333333',
        'license' => 'QAZ321',
        'team' => 'Team Beta',
    ]);

    Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participantA->id,
        'status' => 'confirmada',
        'participated' => true,
        'result_position' => 1,
        'result_points' => 25,
    ]);

    Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participantB->id,
        'status' => 'confirmada',
        'participated' => false,
        'result_position' => 2,
        'result_points' => 18,
    ]);

    $this->actingAs($pilot)
        ->get('/eventos/' . $event->id . '/ranking')
        ->assertOk()
        ->assertSee('Alberto Ramos')
        ->assertDontSee('Bruno Torres');
});

it('shows public event states and only offers registration for scheduled events to pilots', function () {
    /** @var \Tests\TestCase $this */
    $pilot = User::factory()->create([
        'name' => 'Pilot Lists User',
        'email' => 'pilotlist@example.com',
        'role' => 'piloto',
    ]);

    $event = \App\Models\Evento::create([
        'title' => 'Evento programado del piloto',
        'description' => 'Evento disponible',
        'event_date' => now()->addDays(5)->toDateString(),
        'location' => 'Circuito Sur',
        'status' => 'programado',
    ]);
    \App\Models\Evento::create([
        'title' => 'Evento en curso del piloto',
        'event_date' => now()->toDateString(),
        'location' => 'Circuito Central',
        'status' => 'en_curso',
    ]);
    \App\Models\Evento::create([
        'title' => 'Evento finalizado del piloto',
        'event_date' => now()->subDay()->toDateString(),
        'location' => 'Circuito Norte',
        'status' => 'finalizado',
        'results_published' => true,
    ]);

    $this->actingAs($pilot)
        ->get('/eventos')
        ->assertOk()
        ->assertSee('Eventos para pilotos')
        ->assertSee('Evento programado del piloto')
        ->assertSee('Evento en curso del piloto')
        ->assertSee('Evento finalizado del piloto')
        ->assertSee('Ver evento')
        ->assertSee('Inscribirse')
        ->assertSee('Resultados');

    $this->actingAs($pilot)
        ->get('/eventos?status=finalizados')
        ->assertOk()
        ->assertSee('Evento finalizado del piloto')
        ->assertDontSee('Evento programado del piloto');

    $this->actingAs($pilot)
        ->get('/eventos/programados')
        ->assertOk()
        ->assertSee('Eventos programados');

    $this->actingAs($pilot)
        ->get('/eventos/' . $event->id)
        ->assertOk()
        ->assertSee('Detalles del evento');

    $this->actingAs($pilot)
        ->get('/inscripciones/crear')
        ->assertOk()
        ->assertSee('Evento')
        ->assertDontSee('Participante')
        ->assertDontSee('Estado');
});
