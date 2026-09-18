<?php

use App\Models\Event;
use App\Models\OrganizerRequest;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\User;
use Tests\TestCase;

it('allows a pilot to request the organizer role and an admin to approve it', function () {
    /** @var TestCase $this */
    $pilot = User::factory()->create(['role' => 'piloto']);
    $admin = User::factory()->create(['role' => 'administrador']);

    $this->actingAs($pilot)
        ->post('/solicitud-organizador', ['reason' => 'Tengo experiencia organizando competencias.'])
        ->assertRedirect('/dashboard');

    $request = OrganizerRequest::first();

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
    $admin = User::factory()->create(['role' => 'administrador']);
    $pilot = User::factory()->create([
        'role' => 'piloto',
        'verification_status' => 'pendiente',
        'verification_result' => 'Documento pendiente de revisión.',
    ]);

    $this->actingAs($admin)
        ->patch('/admin/usuarios/' . $pilot->id, ['verification_status' => 'verificada'])
        ->assertRedirect('/admin/usuarios');

    expect($pilot->fresh()->verification_status)->toBe('verificada')
        ->and($pilot->fresh()->verified_at)->not->toBeNull();

    $this->actingAs($admin)
        ->patch('/admin/usuarios/' . $pilot->id, ['verification_status' => 'rechazada'])
        ->assertRedirect('/admin/usuarios');

    expect($pilot->fresh()->verification_status)->toBe('rechazada')
        ->and($pilot->fresh()->verified_at)->toBeNull();
});

it('allows an administrator to view a pilot verification document and selfie', function () {
    /** @var TestCase $this */
    $admin = User::factory()->create(['role' => 'administrador']);
    $pilot = User::factory()->create([
        'role' => 'piloto',
        'document_path' => 'verification-documents/document.png',
        'selfie_path' => 'verification-selfies/selfie.png',
    ]);

    \Illuminate\Support\Facades\Storage::fake('local');
    \Illuminate\Support\Facades\Storage::disk('local')->put('verification-documents/document.png', 'document');
    \Illuminate\Support\Facades\Storage::disk('local')->put('verification-selfies/selfie.png', 'selfie');

    $this->actingAs($admin)
        ->get('/admin/usuarios/' . $pilot->id . '/documento')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/usuarios/' . $pilot->id . '/selfie')
        ->assertOk();
});

it('allows visitors to view events, categories and published results', function () {
    /** @var TestCase $this */
    $event = Event::create([
        'title' => 'Evento público',
        'description' => 'Competencia abierta.',
        'event_date' => '2026-09-20',
        'location' => 'Cochabamba',
        'status' => 'finalizado',
    ]);

    $participant = Participant::create([
        'name' => 'Piloto Público',
        'document' => '90909090',
        'email' => 'publico@example.com',
    ]);

    Registration::create([
        'event_id' => $event->id,
        'participant_id' => $participant->id,
        'status' => 'confirmada',
        'result_position' => 1,
        'result_points' => 25,
    ]);

    $this->get('/eventos')->assertOk()->assertSee('Evento público');
    $this->get('/categorias')->assertOk();
    $this->get('/eventos/' . $event->id . '/resultados')->assertOk()->assertSee('Piloto Público')->assertSee('25');
});
