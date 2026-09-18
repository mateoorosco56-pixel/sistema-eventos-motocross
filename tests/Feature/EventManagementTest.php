<?php

use App\Models\Event;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;

uses(WithFaker::class);

it('allows an organizer to create an event', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $category = Category::create(['name' => 'MX1']);

    $this->actingAs($user)
        ->post('/eventos', [
            'title' => 'Gran Premio AMC',
            'description' => 'Carrera de motocross regional.',
            'event_date' => '2026-09-15',
            'location' => 'Cochabamba',
            'status' => 'programado',
            'category_ids' => [$category->id],
        ])
        ->assertRedirect('/eventos');

    $this->assertDatabaseHas('events', [
        'title' => 'Gran Premio AMC',
        'location' => 'Cochabamba',
        'status' => 'programado',
    ]);
});

it('shows available categories when creating an event', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    Category::create(['name' => 'MX1']);

    $this->actingAs($user)
        ->get('/eventos/crear')
        ->assertOk()
        ->assertSee('Categorías disponibles')
        ->assertSee('MX1');
});

it('shows the event list to an authenticated organizer', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $this->actingAs($user)
        ->get('/eventos')
        ->assertOk()
        ->assertSee('Eventos');
});

it('shows a numeric badge next to the navigation events link for pilots when there are new event notifications and clears it after viewing them', function () {
    $pilot = User::factory()->create(['role' => 'piloto']);
    $event = Event::create([
        'title' => 'Evento con aviso',
        'description' => 'Prueba de notificación.',
        'event_date' => '2026-10-05',
        'location' => 'Sucre',
        'status' => 'programado',
    ]);

    $pilot->notify(new \App\Notifications\EventPublished($event->id, $event->title));

    $response = $this->actingAs($pilot)
        ->get('/eventos');

    $response->assertOk();
    $this->assertStringContainsString('Eventos', $response->getContent());
    $this->assertStringContainsString('badge bg-danger rounded-pill ms-1', $response->getContent());
    $this->assertStringContainsString('>1<', $response->getContent());

    $this->assertSame(0, $pilot->fresh()->unreadNotifications->where('type', \App\Notifications\EventPublished::class)->count());
});

it('allows an organizer to update an event', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $event = Event::create([
        'title' => 'Gran Premio AMC',
        'description' => 'Carrera de motocross regional.',
        'event_date' => '2026-09-15',
        'location' => 'Cochabamba',
        'status' => 'programado',
    ]);

    $category = Category::create(['name' => 'MX2']);

    $this->actingAs($user)
        ->patch('/eventos/' . $event->id, [
            'title' => 'Gran Premio AMC 2026',
            'description' => 'Carrera revisada.',
            'event_date' => '2026-09-20',
            'location' => 'Santa Cruz',
            'status' => 'finalizado',
            'category_ids' => [$category->id],
        ])
        ->assertRedirect('/eventos');

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'title' => 'Gran Premio AMC 2026',
        'location' => 'Santa Cruz',
        'status' => 'finalizado',
    ]);
});

it('allows an organizer to delete an event', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $event = Event::create([
        'title' => 'Evento a eliminar',
        'description' => 'Debe desaparecer.',
        'event_date' => '2026-10-01',
        'location' => 'La Paz',
        'status' => 'programado',
    ]);

    $this->actingAs($user)
        ->delete('/eventos/' . $event->id)
        ->assertRedirect('/eventos');

    $this->assertDatabaseMissing('events', [
        'id' => $event->id,
    ]);
});
