<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('dashboard-capable roles land on the dashboard', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertStatus(200);
})->with(['super_admin', 'project_manager', 'engineer', 'contractor']);

test('roles without dashboard access are redirected to their home instead of a 403', function (string $role, string $home) {
    $user = User::factory()->create();
    $user->assignRole($role);
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertRedirect(route($home));
})->with([
    'worker'    => ['worker', 'tasks.my-tasks'],
    'inspector' => ['inspector', 'inspector.dashboard'],
    'client'    => ['client', 'projects.index'],
]);
