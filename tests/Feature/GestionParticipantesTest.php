<?php

use App\Models\Participante;
use App\Models\User;

it('allows an organizer to create a participant', function () {
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $this->actingAs($user)
        ->post('/participantes', [
            'name' => 'Marco Ruiz',
            'document' => '12345678',
            'email' => 'marco@example.com',
            'phone' => '76543210',
            'license' => 'A-2026',
            'team' => 'AMC Racing',
        ])
        ->assertRedirect('/participantes');

    $this->assertDatabaseHas('participantes', [
        'name' => 'Marco Ruiz',
        'email' => 'marco@example.com',
    ]);
});

it('shows the participant list to an authenticated organizer', function () {
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $this->actingAs($user)
        ->get('/participantes')
        ->assertOk()
        ->assertSee('Participantes');
});

it('allows an organizer to update a participant', function () {
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $participant = Participante::create([
        'name' => 'Luisa Velez',
        'document' => '87654321',
        'email' => 'luisa@example.com',
        'phone' => '70000000',
        'license' => 'B-2025',
        'team' => 'Team A',
    ]);

    $this->actingAs($user)
        ->patch('/participantes/' . $participant->id, [
            'name' => 'Luisa Velez Castillo',
            'document' => '87654321',
            'email' => 'luisa.nueva@example.com',
            'phone' => '71111111',
            'license' => 'B-2026',
            'team' => 'Team B',
        ])
        ->assertRedirect('/participantes');

    $this->assertDatabaseHas('participantes', [
        'id' => $participant->id,
        'name' => 'Luisa Velez Castillo',
        'email' => 'luisa.nueva@example.com',
    ]);
});

it('allows an organizer to delete a participant', function () {
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $participant = Participante::create([
        'name' => 'Pedro Mendez',
        'document' => '11112222',
        'email' => 'pedro@example.com',
        'phone' => '72000000',
        'license' => 'C-2026',
        'team' => 'Team C',
    ]);

    $this->actingAs($user)
        ->delete('/participantes/' . $participant->id)
        ->assertRedirect('/participantes');

    $this->assertDatabaseMissing('participantes', [
        'id' => $participant->id,
    ]);
});
