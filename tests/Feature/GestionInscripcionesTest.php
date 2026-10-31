<?php

use App\Models\Categoria;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\Inscripcion;
use App\Models\User;
use App\Notifications\NuevaSolicitudInscripcion;
use Illuminate\Support\Facades\Notification;

it('allows a pilot to register for an event', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'verified',
    ]);

    $category = Categoria::create(['name' => 'MX Independiente']);

    $event = Evento::create([
        'title' => 'Moto GP AMC',
        'description' => 'Evento de prueba.',
        'event_date' => '2026-11-10',
        'location' => 'Cochabamba',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);

    $participant = Participante::create([
        'name' => 'Pablo Flores',
        'document' => '45678901',
        'email' => 'pablo@example.com',
        'phone' => '70123456',
        'license' => 'P-2026',
        'team' => 'Team Uno',
    ]);

    $this->actingAs($user)
        ->post('/inscripciones', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'pendiente',
        ])
        ->assertRedirectToRoute('piloto.inscripciones.show', Inscripcion::latest('id')->first());

    $this->assertDatabaseHas('inscripciones', [
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'pendiente',
    ]);
});

it('requires a pilot to provide personal details, category and race number when registering', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'piloto',
        'name' => 'Mateo Rios',
        'email' => 'mateo.piloto@example.com',
        'verification_status' => 'verified',
    ]);

    $category = Categoria::create([
        'name' => 'MX1',
        'description' => 'Categoria elite',
    ]);

    $event = Evento::create([
        'title' => 'Copa Nacional',
        'description' => 'Competencia nacional.',
        'event_date' => '2026-11-25',
        'location' => 'Santa Cruz',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->post('/inscripciones', [
            'event_id' => $event->id,
            'name' => 'Mateo Rios',
            'document' => '12345678',
            'email' => 'mateo.piloto@example.com',
            'phone' => '78901234',
            'category_id' => $category->id,
            'dorsal' => 27,
        ])
        ->assertRedirectToRoute('piloto.inscripciones.show', Inscripcion::latest('id')->first());

    $this->assertDatabaseHas('participantes', [
        'name' => 'Mateo Rios',
        'document' => '12345678',
        'email' => 'mateo.piloto@example.com',
        'phone' => '78901234',
    ]);

    $participant = Participante::where('email', 'mateo.piloto@example.com')->first();

    $this->assertDatabaseHas('inscripciones', [
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'category_id' => $category->id,
        'dorsal' => 27,
        'status' => 'pendiente',
    ]);
});

it('keeps participant data independent between pilot registrations', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'verified',
    ]);

    $category = Categoria::create(['name' => 'MX Independiente']);

    $firstEvent = Evento::create([
        'title' => 'Primera carrera',
        'event_date' => '2026-11-10',
        'location' => 'Cochabamba',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);
    $secondEvent = Evento::create([
        'title' => 'Segunda carrera',
        'event_date' => '2026-11-17',
        'location' => 'Santa Cruz',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);

    foreach ([[$firstEvent, 'Ana Paola'], [$secondEvent, 'Maria'] ] as [$event, $name]) {
        $this->actingAs($user)->post('/inscripciones', [
            'event_id' => $event->id,
            'name' => $name,
            'document' => '12345678',
            'email' => $user->email,
            'phone' => '70000000',
            'category_id' => $category->id,
            'dorsal' => 10,
        ])->assertRedirectToRoute('piloto.inscripciones.show', Inscripcion::latest('id')->first());
    }

    expect(Participante::whereIn('name', ['Ana Paola', 'Maria'])->count())->toBe(2);
});

it('shows the registration list to an authenticated pilot', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'piloto',
    ]);

    $this->actingAs($user)
        ->get('/inscripciones')
        ->assertOk()
        ->assertSee('Inscripciones');
});

it('shows a pilot registration as a card with a payment action', function () {
    $pilot = User::factory()->create([
        'role' => 'piloto',
    ]);
    $event = Evento::create([
        'title' => 'Campeonato del piloto',
        'event_date' => now()->addDays(8)->toDateString(),
        'location' => 'Circuito Central',
        'status' => 'programado',
    ]);
    $participant = Participante::create([
        'name' => $pilot->name,
        'document' => 'CARD-001',
        'email' => $pilot->email,
    ]);
    Inscripcion::create([
        'event_id' => $event->id,
        'user_id' => $pilot->id,
        'participant_id' => $participant->id,
        'status' => 'pendiente',
    ]);

    $this->actingAs($pilot)
        ->get('/inscripciones')
        ->assertOk()
        ->assertSee('Activas')
        ->assertSee('Historial')
        ->assertSee('Campeonato del piloto')
        ->assertSee('Ver pago')
        ->assertDontSee('Lista de pilotos inscritos');
});

it('shows a compact payment screen without event location or pilot details', function () {
    /** @var \Tests\TestCase $this */
    $pilot = User::factory()->create(['role' => 'piloto']);
    $category = Categoria::create(['name' => 'MX 125cc']);
    $event = Evento::create([
        'title' => 'Campeonato Departamental',
        'description' => 'Descripción que no debe aparecer en el pago.',
        'event_date' => '2026-12-18',
        'location' => 'Circuito Central',
        'status' => 'programado',
        'entry_fee' => 150,
        'payment_instructions' => 'Transferencia bancaria',
    ]);
    $event->categories()->attach($category->id, ['entry_fee' => 85]);
    $participant = Participante::create([
        'name' => 'Nombre privado del piloto',
        'document' => '12345678',
        'email' => $pilot->email,
    ]);
    $registration = Inscripcion::create([
        'event_id' => $event->id,
        'user_id' => $pilot->id,
        'participant_id' => $participant->id,
        'category_id' => $category->id,
        'status' => 'pendiente',
    ]);

    $this->actingAs($pilot)
        ->get(route('piloto.inscripciones.show', $registration))
        ->assertOk()
        ->assertSee('Campeonato Departamental')
        ->assertSee('MX 125cc')
        ->assertSee('Bs. 85,00')
        ->assertDontSee('Bs. 150,00')
        ->assertSee('Transferencia por QR')
        ->assertSee('Escanea el código QR para realizar el pago.')
        ->assertSee('Sube una imagen del comprobante')
        ->assertSee('Enviar comprobante')
        ->assertDontSee('Circuito Central')
        ->assertDontSee('18/12/2026')
        ->assertDontSee('Nombre privado del piloto')
        ->assertDontSee('Descripción que no debe aparecer en el pago.')
        ->assertDontSee('Datos del piloto')
        ->assertDontSee('Ubicación del circuito');
});

it('shows accept and reject actions for organizer pending registrations', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $event = Evento::create([
        'title' => 'Solicitud pendiente',
        'description' => 'Prueba.',
        'event_date' => '2026-12-18',
        'location' => 'Tarija',
        'status' => 'programado',
    ]);

    $participant = Participante::create([
        'name' => 'Lucas Perez',
        'document' => '11223344',
        'email' => 'lucas@example.com',
        'phone' => '71111111',
        'license' => 'L-1',
        'team' => 'Team Cuatro',
    ]);

    $registration = \App\Models\Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'pendiente',
    ]);

    $this->actingAs($user)
        ->get('/inscripciones')
        ->assertOk()
        ->assertSee('Aceptar')
        ->assertSee('Rechazar');
});

it('shows organizer payment controls without the generic update button and supports rejecting the registration', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $event = Evento::create([
        'title' => 'Evento de revisión',
        'description' => 'Prueba de flujo.',
        'event_date' => '2026-12-12',
        'location' => 'La Paz',
        'status' => 'programado',
    ]);

    $participant = Participante::create([
        'name' => 'Ruben Flores',
        'document' => '22334455',
        'email' => 'ruben@example.com',
        'phone' => '78889999',
        'license' => 'R-2',
        'team' => 'Team Dos',
    ]);

    $registration = \App\Models\Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'pendiente',
        'payment_status' => 'en_revision',
        'payment_proof_path' => 'payment-proofs/test.jpg',
    ]);

    $this->actingAs($user)
        ->get("/inscripciones/{$registration->id}/editar")
        ->assertOk()
        ->assertSee('Aceptar pago e inscripción')
        ->assertSee('Rechazar pago e inscripción')
        ->assertDontSee('Actualizar inscripción');

    $this->actingAs($user)
        ->patch("/inscripciones/{$registration->id}/pago", [
            'payment_status' => 'rechazado',
            'status' => 'rechazada',
        ])
        ->assertRedirect();

    $registration->refresh();
    expect($registration->payment_status)->toBe('rechazado')
        ->and($registration->status)->toBe('rechazada');
});

it('confirms a registration after approving payment and shows the accepted state', function () {
    $organizer = User::factory()->create(['role' => 'organizador']);
    $event = Evento::create([
        'title' => 'Evento aprobado',
        'event_date' => '2026-12-18',
        'location' => 'Cochabamba',
        'status' => 'programado',
    ]);
    $participant = Participante::create([
        'name' => 'Piloto Prueba',
        'document' => 'DOC-APROBADO',
        'email' => 'aprobado@example.com',
        'phone' => '70000000',
    ]);
    $registration = Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'pendiente',
        'payment_status' => 'en_revision',
        'payment_proof_path' => 'payment-proofs/approved.jpg',
    ]);

    $this->actingAs($organizer)
        ->from(route('inscripciones.edit', $registration))
        ->patch(route('inscripciones.validate-payment', $registration), [
            'payment_status' => 'aprobado',
            'status' => 'confirmada',
        ])
        ->assertRedirect(route('inscripciones.edit', $registration))
        ->assertSessionHas('success', 'Pago aprobado e inscripción confirmada.');

    expect($registration->fresh()->payment_status)->toBe('aprobado')
        ->and($registration->fresh()->status)->toBe('confirmada');

    $response = $this->get(route('inscripciones.edit', $registration));
    $response->assertOk()
        ->assertSee('Validar inscripción')
        ->assertSee('Pago e inscripción aceptada.')
        ->assertDontSee('Pago aprobado e inscripción confirmada.')
        ->assertDontSee('Pago aprobado e inscripción aceptada.')
        ->assertDontSee('Editar inscripción')
        ->assertSee('value="Aprobado"', false)
        ->assertSee('value="Aceptada"', false)
        ->assertDontSee('Aceptar pago e inscripción');

    expect(substr_count($response->getContent(), 'Descargar comprobante'))->toBe(1);
});

it('shows an error instead of approving a registration with an occupied dorsal', function () {
    $organizer = User::factory()->create(['role' => 'organizador']);
    $category = Categoria::create(['name' => 'MX1']);
    $event = Evento::create([
        'title' => 'Evento dorsal ocupado',
        'event_date' => '2026-12-19',
        'location' => 'Cochabamba',
        'status' => 'programado',
    ]);
    $acceptedParticipant = Participante::create([
        'name' => 'Piloto Aceptado',
        'document' => 'DOC-ACTIVO',
        'email' => 'activo@example.com',
        'phone' => '70000001',
    ]);
    $pendingParticipant = Participante::create([
        'name' => 'Piloto Pendiente',
        'document' => 'DOC-PENDIENTE',
        'email' => 'pendiente@example.com',
        'phone' => '70000002',
    ]);
    Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $acceptedParticipant->id,
        'category_id' => $category->id,
        'dorsal' => 56,
        'status' => 'confirmada',
    ]);
    $registration = Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $pendingParticipant->id,
        'category_id' => $category->id,
        'dorsal' => 56,
        'status' => 'rechazada',
        'payment_status' => 'en_revision',
        'payment_proof_path' => 'payment-proofs/dorsal.jpg',
    ]);

    $this->actingAs($organizer)
        ->from(route('inscripciones.edit', $registration))
        ->patch(route('inscripciones.validate-payment', $registration), [
            'payment_status' => 'aprobado',
            'status' => 'confirmada',
        ])
        ->assertRedirect(route('inscripciones.edit', $registration))
        ->assertSessionHasErrors('payment_status');

    expect($registration->fresh()->payment_status)->toBe('en_revision')
        ->and($registration->fresh()->status)->toBe('rechazada');
});

it('shows the uploaded payment proof in the organizer edit view', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $event = Evento::create([
        'title' => 'Evento con comprobante',
        'description' => 'Prueba de comprobante.',
        'event_date' => '2026-12-20',
        'location' => 'Sucre',
        'status' => 'programado',
    ]);

    $participant = Participante::create([
        'name' => 'Ana Lopez',
        'document' => '33445566',
        'email' => 'ana@example.com',
        'phone' => '79998888',
        'license' => 'A-3',
        'team' => 'Team Tres',
    ]);

    $registration = \App\Models\Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'pendiente',
        'payment_status' => 'en_revision',
        'payment_proof_path' => 'payment-proofs/test.jpg',
    ]);

    $this->actingAs($user)
        ->get("/inscripciones/{$registration->id}/editar")
        ->assertOk()
        ->assertSee('Comprobante enviado')
        ->assertSee('Ver comprobante');
});

it('blocks registration for pilots whose identity has not been verified', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'pending_review',
    ]);

    $category = Categoria::create(['name' => 'MX1']);

    $event = Evento::create([
        'title' => 'Evento piloto sin verificar',
        'description' => 'Prueba.',
        'event_date' => '2026-11-10',
        'location' => 'Cochabamba',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->post('/inscripciones', [
            'event_id' => $event->id,
            'name' => 'Piloto sin verificar',
            'document' => '12345679',
            'email' => $user->email,
            'phone' => '70000000',
            'category_id' => $category->id,
            'dorsal' => 10,
        ])
        ->assertSessionHasErrors('verification_status');

    $this->assertDatabaseCount('inscripciones', 0);

    $rejectedPilot = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'rejected',
    ]);
    $this->actingAs($rejectedPilot)
        ->post('/inscripciones', [
            'event_id' => $event->id,
            'name' => 'Piloto rechazado',
            'document' => '12345680',
            'email' => $rejectedPilot->email,
            'phone' => '70000002',
            'category_id' => $category->id,
            'dorsal' => 11,
        ])
        ->assertSessionHasErrors('verification_status');

    $this->assertDatabaseCount('inscripciones', 0);
});

it('allows verified pilots to register directly to an event', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'verified',
    ]);

    $category = Categoria::create(['name' => 'MX1']);

    $event = Evento::create([
        'title' => 'Evento piloto verificado',
        'description' => 'Prueba.',
        'event_date' => '2026-11-11',
        'location' => 'Santa Cruz',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->post('/inscripciones', [
            'event_id' => $event->id,
            'name' => 'Piloto verificado',
            'document' => '12345670',
            'email' => $user->email,
            'phone' => '70000001',
            'category_id' => $category->id,
            'dorsal' => 11,
        ])
        ->assertRedirectToRoute('piloto.inscripciones.show', Inscripcion::latest('id')->first());

    $this->assertDatabaseHas('inscripciones', [
        'event_id' => $event->id,
        'category_id' => $category->id,
        'dorsal' => 11,
        'status' => 'pendiente',
    ]);
});

it('sends a notification to organizers when a pilot submits a pending registration', function () {
    /** @var \Tests\TestCase $this */
    Notification::fake();

    $organizer = User::factory()->create([
        'role' => 'organizador',
        'email' => 'organizador.notificacion@example.com',
    ]);

    $pilot = User::factory()->create([
        'role' => 'piloto',
        'email' => 'piloto.notificacion@example.com',
        'verification_status' => 'verified',
    ]);

    $event = Evento::create([
        'title' => 'Evento con solicitud',
        'description' => 'Prueba de notificación.',
        'event_date' => '2026-12-20',
        'location' => 'Sucre',
        'status' => 'programado',
    ]);

    $participant = Participante::create([
        'name' => 'Sofia Rojas',
        'document' => '99887766',
        'email' => 'sofia@example.com',
        'phone' => '72222222',
        'license' => 'S-1',
        'team' => 'Team Cinco',
    ]);

    $this->actingAs($pilot)
        ->post('/inscripciones', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'pendiente',
        ]);

    Notification::assertSentTo($organizer, NuevaSolicitudInscripcion::class);
});

it('allows an organizer to update a registration status', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $event = Evento::create([
        'title' => 'Moto Cross Regional',
        'description' => 'Prueba.',
        'event_date' => '2026-12-12',
        'location' => 'Santa Cruz',
        'status' => 'programado',
    ]);

    $participant = Participante::create([
        'name' => 'Ana Salazar',
        'document' => '78901234',
        'email' => 'ana@example.com',
        'phone' => '70000000',
        'license' => 'A2',
        'team' => 'Team Dos',
    ]);

    $registration = \App\Models\Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'pendiente',
    ]);

    $this->actingAs($user)
        ->patch('/inscripciones/' . $registration->id, [
            'status' => 'confirmada',
        ])
        ->assertRedirect('/inscripciones');

    $this->assertDatabaseHas('inscripciones', [
        'id' => $registration->id,
        'status' => 'confirmada',
    ]);
});

it('marks a registration notification as read when the organizer opens it', function () {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create(['role' => 'organizador']);
    $registration = Inscripcion::create([
        'event_id' => Evento::create([
            'title' => 'Evento notificado',
            'description' => 'Prueba.',
            'event_date' => '2026-12-12',
            'location' => 'Santa Cruz',
            'status' => 'programado',
        ])->id,
        'participant_id' => Participante::create([
            'name' => 'Piloto notificado',
            'document' => '12344321',
            'email' => 'notificado@example.com',
        ])->id,
        'status' => 'pendiente',
    ]);

    $organizer->notify(new \App\Notifications\NuevaSolicitudInscripcion(
        $registration->id,
        'Evento notificado',
        'Piloto notificado',
    ));

    $notification = $organizer->fresh()->unreadNotifications->first();

    $this->actingAs($organizer)
        ->get('/notificaciones/' . $notification->id . '/leer')
        ->assertRedirect('/inscripciones/' . $registration->id . '/editar');

    expect($organizer->fresh()->notifications()->find($notification->id)->read_at)->not->toBeNull();
});

it('shows result notifications with a readable message in the admin menu', function () {
    /** @var \Tests\TestCase $this */
    $admin = User::factory()->create(['role' => 'administrador']);
    $event = Evento::create([
        'title' => 'Evento finalizado',
        'description' => 'Prueba de resultados.',
        'event_date' => '2026-12-15',
        'location' => 'Oruro',
        'status' => 'finalizado',
    ]);

    $admin->notify(new \App\Notifications\ResultadoPublicado($event->id, $event->title));

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Nuevo resultado publicado')
        ->assertSee('Ya puedes consultar los resultados de Evento finalizado.');
});

it('allows an organizer to delete a registration', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $event = Evento::create([
        'title' => 'Evento eliminable',
        'description' => 'Prueba.',
        'event_date' => '2026-12-20',
        'location' => 'La Paz',
        'status' => 'programado',
    ]);

    $participant = Participante::create([
        'name' => 'Elena M.',
        'document' => '98765432',
        'email' => 'elena@example.com',
        'phone' => '73000000',
        'license' => 'R-2026',
        'team' => 'Team Tres',
    ]);

    $registration = \App\Models\Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'pendiente',
    ]);

    $this->actingAs($user)
        ->delete('/inscripciones/' . $registration->id)
        ->assertRedirect('/inscripciones');

    $this->assertDatabaseMissing('inscripciones', [
        'id' => $registration->id,
    ]);
});

it('cancels a pilot registration with a reason and releases its dorsal', function () {
    /** @var \Tests\TestCase $this */
    $pilot = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'verified',
    ]);
    $category = Categoria::create(['name' => 'MX Cancelación']);
    $event = Evento::create([
        'title' => 'Evento cancelable',
        'event_date' => '2026-12-20',
        'location' => 'La Paz',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);
    $participant = Participante::create([
        'name' => $pilot->name,
        'document' => 'CANCEL-1',
        'email' => $pilot->email,
    ]);
    $registration = Inscripcion::create([
        'event_id' => $event->id,
        'user_id' => $pilot->id,
        'participant_id' => $participant->id,
        'category_id' => $category->id,
        'dorsal' => 77,
        'status' => 'pendiente',
    ]);

    $this->actingAs($pilot)
        ->delete('/mis-inscripciones/' . $registration->id, [
            'cancellation_reason' => 'No podré asistir.',
        ])
        ->assertRedirect('/inscripciones');

    expect($registration->fresh()->status)->toBe('cancelada')
        ->and($registration->fresh()->cancellation_reason)->toBe('No podré asistir.');

    $replacementPilot = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'verified',
    ]);

    $this->actingAs($replacementPilot)
        ->post('/inscripciones', [
            'event_id' => $event->id,
            'name' => 'Piloto reemplazo',
            'document' => 'REPLACE-1',
            'email' => $replacementPilot->email,
            'phone' => '70000000',
            'category_id' => $category->id,
            'dorsal' => 77,
        ])
        ->assertRedirect();
});

it('enforces dorsal uniqueness per event and category', function () {
    /** @var \Tests\TestCase $this */
    $category = Categoria::create(['name' => 'MX1']);
    $otherCategory = Categoria::create(['name' => 'MX2']);
    $event = Evento::create([
        'title' => 'Copa dorsal',
        'event_date' => '2026-11-10',
        'location' => 'Cochabamba',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);
    $event->categories()->attach($otherCategory);

    $firstPilot = User::factory()->create(['role' => 'piloto', 'email' => 'first@example.com', 'verification_status' => 'verified']);
    $secondPilot = User::factory()->create(['role' => 'piloto', 'email' => 'second@example.com', 'verification_status' => 'verified']);

    $registrationData = fn (string $email, int $categoryId, int $dorsal, ?int $eventId = null) => [
        'event_id' => $eventId ?? $event->id,
        'name' => 'Piloto ' . $email,
        'document' => 'DOC-' . $dorsal . '-' . $categoryId,
        'email' => $email,
        'phone' => '70000000',
        'category_id' => $categoryId,
        'dorsal' => $dorsal,
    ];

    $this->actingAs($firstPilot)
        ->post('/inscripciones', $registrationData($firstPilot->email, $category->id, 25))
        ->assertRedirect();

    $this->actingAs($secondPilot)
        ->post('/inscripciones', $registrationData($secondPilot->email, $category->id, 25))
        ->assertSessionHasErrors(['dorsal' => 'El dorsal 25 ya está ocupado en la categoría MX1. Por favor, selecciona otro número.']);

    $this->actingAs($secondPilot)
        ->post('/inscripciones', $registrationData($secondPilot->email, $otherCategory->id, 25))
        ->assertRedirect();

    $otherEvent = Evento::create([
        'title' => 'Otra copa dorsal',
        'event_date' => '2026-11-17',
        'location' => 'Sacaba',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);

    $this->actingAs($secondPilot)
        ->post('/inscripciones', $registrationData($secondPilot->email, $category->id, 25, $otherEvent->id))
        ->assertRedirect();
});

it('rejects registrations after the deadline, when the event is full, and when the pilot is duplicated', function () {
    /** @var \Tests\TestCase $this */
    $category = Categoria::create(['name' => 'MX Reglas']);
    $pilot = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'verified',
    ]);
    $event = Evento::create([
        'title' => 'Evento con reglas',
        'event_date' => '2026-12-10',
        'registration_deadline' => '2026-09-16',
        'location' => 'Cochabamba',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);

    $this->actingAs($pilot)
        ->post('/inscripciones', [
            'event_id' => $event->id,
            'name' => 'Piloto fuera de plazo',
            'document' => 'RULE-1',
            'email' => $pilot->email,
            'phone' => '70000000',
            'category_id' => $category->id,
            'dorsal' => 41,
        ])
        ->assertSessionHasErrors('event_id');

    $event->update([
        'registration_deadline' => '2026-12-01',
        'max_participants' => 1,
    ]);

    $participant = Participante::create([
        'name' => 'Piloto ya inscrito',
        'document' => 'RULE-2',
        'email' => 'already@example.com',
    ]);
    Inscripcion::create([
        'event_id' => $event->id,
        'user_id' => $pilot->id,
        'participant_id' => $participant->id,
        'category_id' => $category->id,
        'dorsal' => 42,
        'status' => 'pendiente',
    ]);

    $this->actingAs($pilot)
        ->post('/inscripciones', [
            'event_id' => $event->id,
            'name' => 'Duplicado',
            'document' => 'RULE-3',
            'email' => $pilot->email,
            'phone' => '70000000',
            'category_id' => $category->id,
            'dorsal' => 43,
        ])
        ->assertSessionHasErrors('event_id');

    $newPilot = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'verified',
    ]);

    $this->actingAs($newPilot)
        ->post('/inscripciones', [
            'event_id' => $event->id,
            'name' => 'Piloto sin cupo',
            'document' => 'RULE-4',
            'email' => $newPilot->email,
            'phone' => '70000000',
            'category_id' => $category->id,
            'dorsal' => 44,
        ])
        ->assertSessionHasErrors('event_id');
});

it('validates dorsal changes made by an organizer', function () {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create(['role' => 'organizador']);
    $category = Categoria::create(['name' => 'MX1']);
    $event = Evento::create([
        'title' => 'Cambio de dorsal',
        'event_date' => '2026-12-10',
        'location' => 'Cochabamba',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);
    $firstParticipant = Participante::create(['name' => 'Piloto Uno', 'document' => '1', 'email' => 'one@example.com']);
    $secondParticipant = Participante::create(['name' => 'Piloto Dos', 'document' => '2', 'email' => 'two@example.com']);
    $firstRegistration = Inscripcion::create(['event_id' => $event->id, 'participant_id' => $firstParticipant->id, 'category_id' => $category->id, 'dorsal' => 25, 'status' => 'pendiente']);
    $secondRegistration = Inscripcion::create(['event_id' => $event->id, 'participant_id' => $secondParticipant->id, 'category_id' => $category->id, 'dorsal' => 32, 'status' => 'pendiente']);

    $this->actingAs($organizer)
        ->patch('/inscripciones/' . $secondRegistration->id, ['status' => 'pendiente', 'dorsal' => 25])
        ->assertSessionHasErrors(['dorsal' => 'El dorsal 25 ya está ocupado en la categoría MX1. Por favor, selecciona otro número.']);

    $this->actingAs($organizer)
        ->patch('/inscripciones/' . $secondRegistration->id, ['status' => 'pendiente', 'dorsal' => 40])
        ->assertRedirect();

    expect($secondRegistration->fresh()->dorsal)->toBe(40);
});
