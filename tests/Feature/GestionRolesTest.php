<?php

use App\Models\Evento;
use App\Models\SolicitudOrganizador;
use App\Models\Participante;
use App\Models\Inscripcion;
use App\Models\User;
use App\Notifications\EventoPublicado;
use Tests\TestCase;

it('allows a pilot to request the organizer role and an admin to approve it', function () {
    /** @var TestCase $this */
    $pilot = User::factory()->create(['role' => 'piloto']);
    $admin = User::factory()->create(['role' => 'administrador']);

    $this->actingAs($pilot)
        ->post('/solicitud-organizador', ['reason' => 'Tengo experiencia organizando competencias.'])
        ->assertRedirect('/dashboard');

    $request = SolicitudOrganizador::first();

    $this->actingAs($admin)
        ->patch('/admin/solicitudes-organizador/' . $request->id, ['status' => 'aprobada'])
        ->assertRedirect('/admin/solicitudes-organizador');

    expect($pilot->fresh()->role)->toBe('organizador');
    expect($request->fresh()->status)->toBe('aprobada');
});

it('allows an administrator to change another user role and prevents self-demotion', function () {
    /** @var TestCase $this */
    $admin = User::factory()->create(['role' => 'administrador']);
    $user = User::factory()->create(['role' => 'usuario']);

    $this->actingAs($admin)
        ->patch('/admin/usuarios/' . $user->id, ['role' => 'organizador'])
        ->assertRedirect('/admin/usuarios');

    expect($user->fresh()->role)->toBe('organizador');

    $this->actingAs($admin)
        ->patch('/admin/usuarios/' . $admin->id, ['role' => 'usuario'])
        ->assertSessionHasErrors('role');
});

it('allows an administrator to review a pilot verification request', function () {
    /** @var TestCase $this */
    \Illuminate\Support\Facades\Storage::fake('local');
    $admin = User::factory()->create(['role' => 'administrador']);
    $pilot = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'pending_review',
        'verification_result' => 'Documento pendiente de revisión.',
        'document_path' => 'verification-documents/review-document.png',
        'selfie_path' => 'verification-selfies/review-selfie.png',
    ]);
    \Illuminate\Support\Facades\Storage::disk('local')->put($pilot->document_path, 'document');
    \Illuminate\Support\Facades\Storage::disk('local')->put($pilot->selfie_path, 'selfie');

    $this->actingAs($admin)
        ->patch('/admin/usuarios/' . $pilot->id, ['verification_status' => 'verified'])
        ->assertRedirect('/admin/usuarios');

    expect($pilot->fresh()->verification_status)->toBe('verified')
        ->and($pilot->fresh()->verification_method)->toBe('manual_admin')
        ->and($pilot->fresh()->verification_reviewed_by)->toBe($admin->id)
        ->and($pilot->fresh()->verification_reviewed_at)->not->toBeNull()
        ->and($pilot->fresh()->verified_at)->not->toBeNull();

    $this->actingAs($admin)
        ->patch('/admin/usuarios/' . $pilot->id, [
            'verification_status' => 'rejected',
            'verification_rejection_reason' => 'El documento no coincide.',
        ])
        ->assertRedirect('/admin/usuarios');

    expect($pilot->fresh()->verification_status)->toBe('rejected')
        ->and($pilot->fresh()->verification_method)->toBe('manual_admin')
        ->and($pilot->fresh()->verification_rejection_reason)->toBe('El documento no coincide.')
        ->and($pilot->fresh()->verified_at)->toBeNull();
});

it('requires a rejection reason when an administrator rejects identity verification', function () {
    /** @var TestCase $this */
    $admin = User::factory()->create(['role' => 'administrador']);
    $pilot = User::factory()->create(['role' => 'piloto', 'verification_status' => 'pending_review']);

    $this->actingAs($admin)
        ->patch('/admin/usuarios/'.$pilot->id, ['verification_status' => 'rejected'])
        ->assertSessionHasErrors('verification_rejection_reason');

    expect($pilot->fresh()->verification_status)->toBe('pending_review');
});

it('does not allow an administrator to approve identity without private evidence files', function () {
    /** @var TestCase $this */
    \Illuminate\Support\Facades\Storage::fake('local');
    $admin = User::factory()->create(['role' => 'administrador']);
    $pilot = User::factory()->create(['role' => 'piloto', 'verification_status' => 'pending_review']);

    $this->actingAs($admin)
        ->patch('/admin/usuarios/'.$pilot->id, ['verification_status' => 'verified'])
        ->assertSessionHasErrors('verification_status');

    expect($pilot->fresh()->verification_status)->toBe('pending_review');
});

it('allows an administrator to view a pilot verification document and selfie', function () {
    /** @var TestCase $this */
    $admin = User::factory()->create(['role' => 'administrador']);
    $pilot = User::factory()->create([
        'role' => 'piloto',
        'document_path' => 'verification-documents/document.png',
        'document_back_path' => 'verification-documents/document-back.png',
        'selfie_path' => 'verification-selfies/selfie.png',
    ]);

    \Illuminate\Support\Facades\Storage::fake('local');
    \Illuminate\Support\Facades\Storage::disk('local')->put('verification-documents/document.png', 'document');
    \Illuminate\Support\Facades\Storage::disk('local')->put('verification-documents/document-back.png', 'document back');
    \Illuminate\Support\Facades\Storage::disk('local')->put('verification-selfies/selfie.png', 'selfie');

    $pilotViewer = User::factory()->create(['role' => 'piloto']);

    $this->actingAs($pilotViewer)
        ->get('/admin/usuarios/' . $pilot->id . '/documento')
        ->assertForbidden();

    $this->actingAs($admin)
        ->get('/admin/usuarios/' . $pilot->id . '/documento')
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->actingAs($admin)
        ->get('/admin/usuarios/' . $pilot->id . '/documento/reverso')
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private');

    $this->actingAs($admin)
        ->get('/admin/usuarios/' . $pilot->id . '/selfie')
        ->assertOk();
});

it('allows visitors to view events, categories and published results', function () {
    /** @var TestCase $this */
    $event = Evento::create([
        'title' => 'Evento público',
        'description' => 'Competencia abierta.',
        'event_date' => '2026-09-20',
        'location' => 'Cochabamba',
        'status' => 'finalizado',
        'results_published' => true,
    ]);

    $participant = Participante::create([
        'name' => 'Piloto Público',
        'document' => '90909090',
        'email' => 'publico@example.com',
    ]);

    Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'confirmada',
        'result_position' => 1,
        'result_points' => 25,
    ]);

    $this->get('/eventos')->assertOk()->assertSee('Evento público');
    $this->get('/categorias')->assertOk();
    $this->get('/eventos/' . $event->id . '/resultados')->assertOk()->assertSee('Piloto Público')->assertSee('25');
    $this->get('/resultados')->assertOk()->assertSee('Piloto Público')->assertSee('25')->assertSee('General');
});

it('shows the pilot results layout with the reference columns and category modes', function () {
    $pilot = User::factory()->create(['role' => 'piloto']);
    $event = Evento::create([
        'title' => 'Fecha final del piloto',
        'event_date' => now()->subDay()->toDateString(),
        'location' => 'Cochabamba',
        'status' => 'finalizado',
        'results_published' => true,
    ]);
    $participant = Participante::create([
        'name' => $pilot->name,
        'document' => 'RESULT-001',
        'email' => $pilot->email,
    ]);
    Inscripcion::create([
        'event_id' => $event->id,
        'user_id' => $pilot->id,
        'participant_id' => $participant->id,
        'status' => 'confirmada',
        'dorsal' => 23,
        'result_position' => 1,
        'result_points' => 50,
    ]);

    $this->actingAs($pilot)
        ->get('/resultados')
        ->assertOk()
        ->assertSee('class="pilot-results-topbar"', false)
        ->assertSee('General')
        ->assertSee('Por categoría')
        ->assertSee('Pos.')
        ->assertSee('Piloto')
        ->assertSee('N.º')
        ->assertSee('Puntos')
        ->assertSee('23')
        ->assertSee('50');
});

it('shows pilot notifications and marks a notification read when opened', function () {
    $pilot = User::factory()->create(['role' => 'piloto']);
    $event = Evento::create([
        'title' => 'Aviso de competencia',
        'event_date' => now()->addDays(6)->toDateString(),
        'location' => 'Cochabamba',
        'status' => 'programado',
    ]);
    $pilot->notify(new EventoPublicado($event->id, $event->title));
    $notification = $pilot->fresh()->unreadNotifications->firstOrFail();

    $this->actingAs($pilot)
        ->get(route('piloto.notificaciones.index'))
        ->assertOk()
        ->assertSee('Notificaciones')
        ->assertSee('Nuevo evento publicado')
        ->assertSee('Aviso de competencia')
        ->assertDontSee('Tu actividad')
        ->assertDontSee('Eventos, resultados y actualizaciones de tus inscripciones.');

    expect(substr_count($this->get(route('piloto.notificaciones.index'))->getContent(), 'class="pilot-flow-notification-card"'))->toBe(1);

    $this->actingAs($pilot)
        ->get(route('notifications.read', $notification->id))
        ->assertRedirect(route('eventos.show', $event));

    expect($notification->fresh()->read_at)->not->toBeNull();
});
