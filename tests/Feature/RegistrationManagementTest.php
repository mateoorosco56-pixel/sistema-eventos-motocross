<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\NewRegistrationRequest;
use Illuminate\Support\Facades\Notification;

it('allows a pilot to register for an event', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'piloto',
    ]);

    $category = Category::create(['name' => 'MX Independiente']);

    $event = Event::create([
        'title' => 'Moto GP AMC',
        'description' => 'Evento de prueba.',
        'event_date' => '2026-11-10',
        'location' => 'Cochabamba',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);

    $participant = Participant::create([
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
        ->assertRedirectToRoute('piloto.inscripciones.show', Registration::latest('id')->first());

    $this->assertDatabaseHas('registrations', [
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
    ]);

    $category = Category::create([
        'name' => 'MX1',
        'description' => 'Categoria elite',
    ]);

    $event = Event::create([
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
        ->assertRedirectToRoute('piloto.inscripciones.show', Registration::latest('id')->first());

    $this->assertDatabaseHas('participants', [
        'name' => 'Mateo Rios',
        'document' => '12345678',
        'email' => 'mateo.piloto@example.com',
        'phone' => '78901234',
    ]);

    $participant = Participant::where('email', 'mateo.piloto@example.com')->first();

    $this->assertDatabaseHas('registrations', [
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
    ]);

    $category = Category::create(['name' => 'MX Independiente']);

    $firstEvent = Event::create([
        'title' => 'Primera carrera',
        'event_date' => '2026-11-10',
        'location' => 'Cochabamba',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);
    $secondEvent = Event::create([
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
        ])->assertRedirectToRoute('piloto.inscripciones.show', Registration::latest('id')->first());
    }

    expect(Participant::whereIn('name', ['Ana Paola', 'Maria'])->count())->toBe(2);
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

it('shows accept and reject actions for organizer pending registrations', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $event = Event::create([
        'title' => 'Solicitud pendiente',
        'description' => 'Prueba.',
        'event_date' => '2026-12-18',
        'location' => 'Tarija',
        'status' => 'programado',
    ]);

    $participant = Participant::create([
        'name' => 'Lucas Perez',
        'document' => '11223344',
        'email' => 'lucas@example.com',
        'phone' => '71111111',
        'license' => 'L-1',
        'team' => 'Team Cuatro',
    ]);

    $registration = \App\Models\Registration::create([
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

    $event = Event::create([
        'title' => 'Evento de revisión',
        'description' => 'Prueba de flujo.',
        'event_date' => '2026-12-12',
        'location' => 'La Paz',
        'status' => 'programado',
    ]);

    $participant = Participant::create([
        'name' => 'Ruben Flores',
        'document' => '22334455',
        'email' => 'ruben@example.com',
        'phone' => '78889999',
        'license' => 'R-2',
        'team' => 'Team Dos',
    ]);

    $registration = \App\Models\Registration::create([
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

it('shows the uploaded payment proof in the organizer edit view', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $event = Event::create([
        'title' => 'Evento con comprobante',
        'description' => 'Prueba de comprobante.',
        'event_date' => '2026-12-20',
        'location' => 'Sucre',
        'status' => 'programado',
    ]);

    $participant = Participant::create([
        'name' => 'Ana Lopez',
        'document' => '33445566',
        'email' => 'ana@example.com',
        'phone' => '79998888',
        'license' => 'A-3',
        'team' => 'Team Tres',
    ]);

    $registration = \App\Models\Registration::create([
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
        'verification_status' => 'pendiente',
    ]);

    $category = Category::create(['name' => 'MX1']);

    $event = Event::create([
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

    $this->assertDatabaseCount('registrations', 0);
});

it('allows verified pilots to register directly to an event', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'verificada',
    ]);

    $category = Category::create(['name' => 'MX1']);

    $event = Event::create([
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
        ->assertRedirectToRoute('piloto.inscripciones.show', Registration::latest('id')->first());

    $this->assertDatabaseHas('registrations', [
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
    ]);

    $event = Event::create([
        'title' => 'Evento con solicitud',
        'description' => 'Prueba de notificación.',
        'event_date' => '2026-12-20',
        'location' => 'Sucre',
        'status' => 'programado',
    ]);

    $participant = Participant::create([
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

    Notification::assertSentTo($organizer, NewRegistrationRequest::class);
});

it('allows an organizer to update a registration status', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $event = Event::create([
        'title' => 'Moto Cross Regional',
        'description' => 'Prueba.',
        'event_date' => '2026-12-12',
        'location' => 'Santa Cruz',
        'status' => 'programado',
    ]);

    $participant = Participant::create([
        'name' => 'Ana Salazar',
        'document' => '78901234',
        'email' => 'ana@example.com',
        'phone' => '70000000',
        'license' => 'A2',
        'team' => 'Team Dos',
    ]);

    $registration = \App\Models\Registration::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'pendiente',
    ]);

    $this->actingAs($user)
        ->patch('/inscripciones/' . $registration->id, [
            'status' => 'confirmada',
        ])
        ->assertRedirect('/inscripciones');

    $this->assertDatabaseHas('registrations', [
        'id' => $registration->id,
        'status' => 'confirmada',
    ]);
});

it('marks a registration notification as read when the organizer opens it', function () {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create(['role' => 'organizador']);
    $registration = Registration::create([
        'event_id' => Event::create([
            'title' => 'Evento notificado',
            'description' => 'Prueba.',
            'event_date' => '2026-12-12',
            'location' => 'Santa Cruz',
            'status' => 'programado',
        ])->id,
        'participant_id' => Participant::create([
            'name' => 'Piloto notificado',
            'document' => '12344321',
            'email' => 'notificado@example.com',
        ])->id,
        'status' => 'pendiente',
    ]);

    $organizer->notify(new \App\Notifications\NewRegistrationRequest(
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
    $event = Event::create([
        'title' => 'Evento finalizado',
        'description' => 'Prueba de resultados.',
        'event_date' => '2026-12-15',
        'location' => 'Oruro',
        'status' => 'finalizado',
    ]);

    $admin->notify(new \App\Notifications\ResultPublished($event->id, $event->title));

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

    $event = Event::create([
        'title' => 'Evento eliminable',
        'description' => 'Prueba.',
        'event_date' => '2026-12-20',
        'location' => 'La Paz',
        'status' => 'programado',
    ]);

    $participant = Participant::create([
        'name' => 'Elena M.',
        'document' => '98765432',
        'email' => 'elena@example.com',
        'phone' => '73000000',
        'license' => 'R-2026',
        'team' => 'Team Tres',
    ]);

    $registration = \App\Models\Registration::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'pendiente',
    ]);

    $this->actingAs($user)
        ->delete('/inscripciones/' . $registration->id)
        ->assertRedirect('/inscripciones');

    $this->assertDatabaseMissing('registrations', [
        'id' => $registration->id,
    ]);
});

it('cancels a pilot registration with a reason and releases its dorsal', function () {
    /** @var \Tests\TestCase $this */
    $pilot = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'verificada',
    ]);
    $category = Category::create(['name' => 'MX Cancelación']);
    $event = Event::create([
        'title' => 'Evento cancelable',
        'event_date' => '2026-12-20',
        'location' => 'La Paz',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);
    $participant = Participant::create([
        'name' => $pilot->name,
        'document' => 'CANCEL-1',
        'email' => $pilot->email,
    ]);
    $registration = Registration::create([
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
        'verification_status' => 'verificada',
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
    $category = Category::create(['name' => 'MX1']);
    $otherCategory = Category::create(['name' => 'MX2']);
    $event = Event::create([
        'title' => 'Copa dorsal',
        'event_date' => '2026-11-10',
        'location' => 'Cochabamba',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);
    $event->categories()->attach($otherCategory);

    $firstPilot = User::factory()->create(['role' => 'piloto', 'email' => 'first@example.com']);
    $secondPilot = User::factory()->create(['role' => 'piloto', 'email' => 'second@example.com']);

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

    $otherEvent = Event::create([
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
    $category = Category::create(['name' => 'MX Reglas']);
    $pilot = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'verificada',
    ]);
    $event = Event::create([
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

    $participant = Participant::create([
        'name' => 'Piloto ya inscrito',
        'document' => 'RULE-2',
        'email' => 'already@example.com',
    ]);
    Registration::create([
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
        'verification_status' => 'verificada',
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
    $category = Category::create(['name' => 'MX1']);
    $event = Event::create([
        'title' => 'Cambio de dorsal',
        'event_date' => '2026-12-10',
        'location' => 'Cochabamba',
        'status' => 'programado',
        'category_id' => $category->id,
    ]);
    $firstParticipant = Participant::create(['name' => 'Piloto Uno', 'document' => '1', 'email' => 'one@example.com']);
    $secondParticipant = Participant::create(['name' => 'Piloto Dos', 'document' => '2', 'email' => 'two@example.com']);
    $firstRegistration = Registration::create(['event_id' => $event->id, 'participant_id' => $firstParticipant->id, 'category_id' => $category->id, 'dorsal' => 25, 'status' => 'pendiente']);
    $secondRegistration = Registration::create(['event_id' => $event->id, 'participant_id' => $secondParticipant->id, 'category_id' => $category->id, 'dorsal' => 32, 'status' => 'pendiente']);

    $this->actingAs($organizer)
        ->patch('/inscripciones/' . $secondRegistration->id, ['status' => 'pendiente', 'dorsal' => 25])
        ->assertSessionHasErrors(['dorsal' => 'El dorsal 25 ya está ocupado en la categoría MX1. Por favor, selecciona otro número.']);

    $this->actingAs($organizer)
        ->patch('/inscripciones/' . $secondRegistration->id, ['status' => 'pendiente', 'dorsal' => 40])
        ->assertRedirect();

    expect($secondRegistration->fresh()->dorsal)->toBe(40);
});
