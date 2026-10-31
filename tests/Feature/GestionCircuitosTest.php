<?php

use App\Models\Categoria;
use App\Models\Circuito;
use App\Models\Evento;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('lets an organizer upload a circuit photo and use it as an event cover', function () {
    /** @var \Tests\TestCase $this */
    Storage::fake('public');
    $organizer = User::factory()->create(['role' => 'organizador']);
    $category = Categoria::create(['name' => 'MX1']);
    $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC');

    $this->actingAs($organizer)
        ->post(route('organizador.circuitos.store'), [
            'name' => 'Circuito Quintanilla',
            'image' => UploadedFile::fake()->createWithContent('quintanilla.png', $pngContent),
        ])
        ->assertRedirect(route('organizador.circuitos.index'));

    $circuit = Circuito::firstOrFail();
    Storage::disk('public')->assertExists($circuit->image_path);

    $this->get(route('organizador.circuitos.index'))
        ->assertOk()
        ->assertSee('Circuito Quintanilla')
        ->assertSee('/storage/'.$circuit->image_path);

    $this->get(route('eventos.create'))
        ->assertOk()
        ->assertSee('Foto de mis circuitos')
        ->assertSee('/storage/'.$circuit->image_path);

    $this->post(route('eventos.store'), [
        'title' => 'Copa de prueba',
        'event_date' => now()->addDays(14)->toDateString(),
        'start_time' => '08:30',
        'location' => 'Cochabamba',
        'status' => 'programado',
        'category_ids' => [$category->id],
        'circuit_id' => $circuit->id,
    ])->assertRedirect(route('eventos.index'));

    $event = Evento::where('title', 'Copa de prueba')->firstOrFail();
    expect($event->circuit_id)->toBe($circuit->id);

    $this->get(route('eventos.show', $event))
        ->assertOk()
        ->assertSee('/storage/'.$circuit->image_path);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('/storage/'.$circuit->image_path);

    $this->get(route('inscripciones.create', ['event_id' => $event->id]))
        ->assertOk()
        ->assertSee('/storage/'.$circuit->image_path);
});

it('opens the selected circuit publicly and shows only its events', function () {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create(['role' => 'organizador']);
    $selectedCircuit = Circuito::create([
        'organizer_id' => $organizer->id,
        'name' => 'Circuito Norte',
        'image_path' => 'circuits/norte.jpg',
    ]);
    $otherCircuit = Circuito::create([
        'organizer_id' => $organizer->id,
        'name' => 'Circuito Sur',
        'image_path' => 'circuits/sur.jpg',
    ]);
    Evento::create([
        'title' => 'Carrera del Norte',
        'event_date' => '2026-12-18',
        'location' => 'Norte',
        'status' => 'programado',
        'circuit_id' => $selectedCircuit->id,
    ]);
    Evento::create([
        'title' => 'Carrera del Sur',
        'event_date' => '2026-12-20',
        'location' => 'Sur',
        'status' => 'programado',
        'circuit_id' => $otherCircuit->id,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('circuitos.show', $selectedCircuit), false);

    $this->get(route('circuitos.index'))
        ->assertOk()
        ->assertSee('Circuito Norte')
        ->assertSee('Circuito Sur');

    $this->get(route('circuitos.show', $selectedCircuit))
        ->assertOk()
        ->assertSee('Circuito Norte')
        ->assertSee('Carrera del Norte')
        ->assertDontSee('Circuito Sur')
        ->assertDontSee('Carrera del Sur');
});

it('prevents organizers from selecting circuits owned by another organizer', function () {
    /** @var \Tests\TestCase $this */
    $organizer = User::factory()->create(['role' => 'organizador']);
    $otherOrganizer = User::factory()->create(['role' => 'organizador']);
    $category = Categoria::create(['name' => 'MX2']);
    $circuit = Circuito::create([
        'organizer_id' => $otherOrganizer->id,
        'name' => 'Circuito privado',
        'image_path' => 'circuits/private.jpg',
    ]);

    $this->actingAs($organizer)
        ->from(route('eventos.create'))
        ->post(route('eventos.store'), [
            'title' => 'Evento no autorizado',
            'event_date' => now()->addDays(20)->toDateString(),
            'start_time' => '09:00',
            'location' => 'Cochabamba',
            'status' => 'programado',
            'category_ids' => [$category->id],
            'circuit_id' => $circuit->id,
        ])
        ->assertSessionHasErrors('circuit_id');

    $this->assertDatabaseMissing('eventos', ['title' => 'Evento no autorizado']);
});
