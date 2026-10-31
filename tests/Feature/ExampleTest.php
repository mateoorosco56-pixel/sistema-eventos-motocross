<?php

use App\Models\Circuito;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\Inscripcion;
use App\Models\User;

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('shows all upcoming events and their covers on the public home page', function () {
    Evento::create([
        'title' => 'Portada en inicio',
        'event_date' => now()->addDays(100)->toDateString(),
        'location' => 'Cochabamba',
        'status' => 'programado',
        'event_background_path' => 'event-backgrounds/home-cover.jpg',
    ]);
    Evento::create([
        'title' => 'Siguiente evento',
        'event_date' => now()->addDays(20)->toDateString(),
        'location' => 'Cochabamba',
        'status' => 'programado',
    ]);
    foreach (range(3, 7) as $number) {
        Evento::create([
            'title' => "Evento $number",
            'event_date' => now()->addDays($number * 10)->toDateString(),
            'location' => 'Cochabamba',
            'status' => 'programado',
        ]);
    }

    $response = $this->get('/');
    $response
        ->assertOk()
        ->assertSee('Próximos eventos')
        ->assertSee('href="#eventos">Eventos</a>', false)
        ->assertSee('href="#resultados">Resultados</a>', false)
        ->assertSee('id="eventos"', false)
        ->assertSee('id="resultados"', false)
        ->assertSee('Portada en inicio')
        ->assertSee('/storage/event-backgrounds/home-cover.jpg')
        ->assertViewHas('upcomingEvents', fn ($events) => $events->count() === 7);
    expect(substr_count($response->getContent(), 'class="event-card"'))->toBe(7);
    expect(substr_count($response->getContent(), "/storage/event-backgrounds/home-cover.jpg')"))->toBe(1);
});

it('shows circuit cards and ranks riders using only published results', function () {
    $organizer = User::factory()->create(['role' => 'organizador']);
    Circuito::create([
        'organizer_id' => $organizer->id,
        'name' => 'Circuito de prueba',
        'image_path' => 'circuits/test.jpg',
    ]);

    $winner = Participante::create([
        'name' => 'Piloto con puntos',
        'document' => 'RANK-001',
        'email' => 'rank-001@example.com',
    ]);
    $unpublishedRider = Participante::create([
        'name' => 'Piloto sin resultados publicados',
        'document' => 'RANK-002',
        'email' => 'rank-002@example.com',
    ]);

    $publishedEvent = Evento::create([
        'title' => 'Competencia con resultados',
        'event_date' => now()->subDays(2)->toDateString(),
        'location' => 'Cochabamba',
        'status' => 'finalizado',
        'results_published' => true,
    ]);
    $unpublishedEvent = Evento::create([
        'title' => 'Competencia sin publicar',
        'event_date' => now()->subDay()->toDateString(),
        'location' => 'Cochabamba',
        'status' => 'finalizado',
        'results_published' => false,
    ]);

    Inscripcion::create([
        'event_id' => $publishedEvent->id,
        'participant_id' => $winner->id,
        'status' => 'confirmada',
        'result_position' => 1,
        'result_points' => 46,
    ]);
    Inscripcion::create([
        'event_id' => $unpublishedEvent->id,
        'participant_id' => $unpublishedRider->id,
        'status' => 'confirmada',
        'result_position' => 1,
        'result_points' => 99,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Circuito de prueba')
        ->assertSee('Piloto con puntos')
        ->assertSee('46')
        ->assertDontSee('Piloto sin resultados publicados');
});
