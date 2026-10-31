<?php

use App\Models\Categoria;
use App\Models\User;
use Tests\TestCase;

it('allows an organizer to create a category', function () {
    /** @var TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $this->actingAs($user)
        ->post('/categorias', [
            'name' => 'MX Senior',
            'description' => 'Categoría para pilotos mayores de 18 años.',
        ])
        ->assertRedirect('/categorias');

    $this->assertDatabaseHas('categorias', [
        'name' => 'MX Senior',
    ]);
});

it('shows the category list to an authenticated organizer', function () {
    /** @var TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $this->actingAs($user)
        ->get('/categorias')
        ->assertOk()
        ->assertSee('Categorías');
});

it('allows an organizer to update a category', function () {
    /** @var TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $category = Categoria::create([
        'name' => 'MX Junior',
        'description' => 'Categoría inicial.',
    ]);

    $this->actingAs($user)
        ->patch('/categorias/' . $category->id, [
            'name' => 'MX Junior 2026',
            'description' => 'Categoría actualizada.',
        ])
        ->assertRedirect('/categorias');

    $this->assertDatabaseHas('categorias', [
        'id' => $category->id,
        'name' => 'MX Junior 2026',
    ]);
});

it('allows an organizer to delete a category', function () {
    /** @var TestCase $this */
    $user = User::factory()->create([
        'role' => 'organizador',
    ]);

    $category = Categoria::create([
        'name' => 'MX Elite',
        'description' => 'Categoría a ser eliminada.',
    ]);

    $this->actingAs($user)
        ->delete('/categorias/' . $category->id)
        ->assertRedirect('/categorias');

    $this->assertDatabaseMissing('categorias', [
        'id' => $category->id,
    ]);
});
