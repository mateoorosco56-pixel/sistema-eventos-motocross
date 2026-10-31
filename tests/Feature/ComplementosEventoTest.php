<?php

use App\Models\Categoria;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\Inscripcion;
use App\Models\User;

it('allows an organizer to save prizes and a starting order for event categories', function () {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create(['role' => 'organizador']);
    $mx1 = Categoria::create(['name' => 'MX1']);
    $mx2 = Categoria::create(['name' => 'MX2']);
    $event = Evento::create([
        'title' => 'Copa AMC',
        'event_date' => '2026-10-20',
        'location' => 'Cochabamba',
        'status' => 'programado',
    ]);
    $event->categories()->sync([$mx1->id, $mx2->id]);

    $this->actingAs($organizer)
        ->patch('/eventos/'.$event->id, [
            'title' => $event->title,
            'event_date' => $event->event_date->format('Y-m-d'),
            'location' => $event->location,
            'status' => $event->status,
            'category_ids' => [$mx1->id, $mx2->id],
            'prizes' => [
                ['category_id' => $mx1->id, 'position' => 1, 'award_type' => 'Trofeo', 'description' => 'Campeón', 'amount' => '1500'],
                ['category_id' => $mx1->id, 'position' => 2, 'award_type' => 'Trofeo', 'description' => null, 'amount' => '1000'],
                ['category_id' => $mx1->id, 'position' => 3, 'award_type' => 'Trofeo', 'description' => null, 'amount' => '500'],
                ['category_id' => $mx2->id, 'position' => 1, 'award_type' => 'Trofeo', 'description' => null, 'amount' => '1000'],
                ['category_id' => $mx2->id, 'position' => 2, 'award_type' => 'Trofeo', 'description' => null, 'amount' => '700'],
                ['category_id' => $mx2->id, 'position' => 3, 'award_type' => 'Trofeo', 'description' => null, 'amount' => '400'],
            ],
            'starting_orders' => [
                ['category_id' => $mx2->id, 'position' => 1, 'scheduled_time' => '09:00'],
                ['category_id' => $mx1->id, 'position' => 2, 'scheduled_time' => '09:30'],
            ],
            'publish_starting_order' => '1',
        ])
        ->assertRedirect('/eventos');

    $this->assertDatabaseHas('premios_evento', [
        'event_id' => $event->id,
        'category_id' => $mx1->id,
        'position' => 1,
        'award_type' => 'Trofeo',
    ]);
    $this->assertDatabaseHas('ordenes_salida_evento', [
        'event_id' => $event->id,
        'category_id' => $mx2->id,
        'position' => 1,
        'scheduled_time' => '09:00',
        'is_published' => true,
    ]);
    $this->assertDatabaseHas('ordenes_salida_evento', [
        'event_id' => $event->id,
        'category_id' => $mx1->id,
        'position' => 2,
        'is_published' => true,
    ]);
});

it('shows only published starting orders and active registrations by category publicly', function () {
    /** @var \Tests\TestCase $this */
    $mx1 = Categoria::create(['name' => 'MX1']);
    $mx2 = Categoria::create(['name' => 'MX2']);
    $event = Evento::create([
        'title' => 'Copa AMC Pública',
        'event_date' => '2026-10-20',
        'location' => 'Cochabamba',
        'latitude' => -17.393,
        'longitude' => -66.157,
        'status' => 'programado',
        'event_background_path' => 'event-backgrounds/test.jpg',
    ]);
    $event->categories()->sync([$mx1->id, $mx2->id]);
    $event->startingOrders()->createMany([
        ['category_id' => $mx2->id, 'position' => 1, 'scheduled_time' => '09:00', 'is_published' => true],
        ['category_id' => $mx1->id, 'position' => 2, 'scheduled_time' => '09:30', 'is_published' => false],
    ]);

    $activeParticipant = Participante::create([
        'name' => 'Piloto Activo',
        'document' => 'DOC-ACTIVO',
        'email' => 'activo@example.test',
    ]);
    $rejectedParticipant = Participante::create([
        'name' => 'Piloto Rechazado',
        'document' => 'DOC-RECHAZADO',
        'email' => 'rechazado@example.test',
    ]);
    Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $activeParticipant->id,
        'category_id' => $mx2->id,
        'dorsal' => 27,
        'status' => 'confirmada',
    ]);
    Inscripcion::create([
        'event_id' => $event->id,
        'participant_id' => $rejectedParticipant->id,
        'category_id' => $mx2->id,
        'dorsal' => 15,
        'status' => 'rechazada',
    ]);

    $this->get('/eventos/'.$event->id)
        ->assertOk()
        ->assertSee('data-bs-target="#event-map-viewer-modal"', false)
        ->assertSee('id="event-map-viewer-modal"', false)
        ->assertDontSee('Fecha límite de inscripción')
        ->assertSee('No definido')
        ->assertDontSee('Este evento no tiene descripción adicional.')
        ->assertSee('Orden de largada')
        ->assertSee('Piloto Activo')
        ->assertSee('#27')
        ->assertDontSee('Piloto Rechazado')
        ->assertDontSee('09:30');
});
