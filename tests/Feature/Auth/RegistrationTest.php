<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'piloto',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs(User::where('email', 'test@example.com')->first());
    expect(User::where('email', 'test@example.com')->value('role'))->toBe('piloto');
    $response->assertRedirect(route('dashboard', absolute: false));
});
