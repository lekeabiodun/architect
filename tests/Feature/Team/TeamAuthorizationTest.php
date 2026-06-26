<?php

use App\Livewire\Team\Index as TeamIndex;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('forbids a worker from opening the team management screen', function () {
    $worker = User::factory()->create();
    $worker->assignRole('worker');

    actingAs($worker);

    Livewire::test(TeamIndex::class)->assertForbidden();
});

it('forbids a view-only role from creating a user via the team component', function () {
    // project_manager may VIEW the team screen but lacks "manage team members".
    $viewer = User::factory()->create();
    $viewer->assignRole('project_manager');

    actingAs($viewer);

    Livewire::test(TeamIndex::class)
        ->set('name', 'Hacker')
        ->set('email', 'hacker@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('role', 'project_manager')
        ->call('createUser')
        ->assertForbidden();

    expect(User::where('email', 'hacker@example.com')->exists())->toBeFalse();
});

it('forbids a view-only role from deleting another user via the team component', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('project_manager');

    $victim = User::factory()->create();

    actingAs($viewer);

    Livewire::test(TeamIndex::class)
        ->call('deleteUser', $victim->id)
        ->assertForbidden();

    expect(User::find($victim->id))->not->toBeNull();
});

it('allows a super admin to manage the team', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    actingAs($admin);

    Livewire::test(TeamIndex::class)->assertOk();
});
