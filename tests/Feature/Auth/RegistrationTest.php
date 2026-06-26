<?php

use Livewire\Volt\Volt;

// Self-service registration is intentionally disabled in this application
// (the `register` route is commented out in routes/auth.php — users are
// created by admins via the Team screen). These tests are skipped until/
// unless public registration is re-enabled.
test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
})->skip('Public registration is disabled in this application.');

test('new users can register', function () {
    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/dashboard');

    $this->assertAuthenticated();
})->skip('Public registration is disabled in this application.');
