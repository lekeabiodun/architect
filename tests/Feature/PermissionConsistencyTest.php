<?php

use App\Models\Phase;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function userAs(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('grants director and project_manager permission-backed time and leave administration', function (string $role) {
    $user = userAs($role);

    // These now resolve purely through the permission system (no hidden role shortcut).
    expect($user->canManageTimeTracking())->toBeTrue()
        ->and($user->canApproveLeave())->toBeTrue()
        ->and($user->hasPermissionTo('manage time entries'))->toBeTrue()
        ->and($user->hasPermissionTo('approve leave requests'))->toBeTrue();
})->with(['director', 'project_manager', 'manager']);

it('does not grant time/leave administration to staff without the permission', function (string $role) {
    $user = userAs($role);

    expect($user->canManageTimeTracking())->toBeFalse()
        ->and($user->canApproveLeave())->toBeFalse();
})->with(['worker', 'contractor', 'engineer', 'inspector']);

it('limits the time/leave permission surface to the two admin capabilities', function () {
    // Clock in/out and submitting leave are self-service (role-gated, no permission).
    // Only the admin capabilities are permission-backed.
    $timeLeavePerms = Permission::query()
        ->where('name', 'like', '%time%')
        ->orWhere('name', 'like', '%leave%')
        ->orWhere('name', 'like', 'clock%')
        ->pluck('name')
        ->sort()
        ->values()
        ->all();

    expect($timeLeavePerms)->toBe(['approve leave requests', 'manage time entries']);
});

it('keeps inspection as a single permission — pass and fail share the inspect ability', function () {
    $inspectionPerms = Permission::query()
        ->where('name', 'like', '%inspect%')
        ->pluck('name')
        ->sort()
        ->values()
        ->all();

    expect($inspectionPerms)->toBe(['inspect tasks']);
});

it('lets a non-client clock in and submit leave without any time/leave permission', function () {
    $worker = userAs('worker');

    expect($worker->hasPermissionTo('manage time entries'))->toBeFalse()
        ->and($worker->can('clockIn', App\Models\TimeEntry::class))->toBeTrue()
        ->and($worker->can('create', App\Models\LeaveRequest::class))->toBeTrue();
});

it('lets the project-designated inspector inspect tasks even without pivot membership', function () {
    $inspector = userAs('inspector');

    // Inspector is set via the project's inspector_id, NOT added to the users pivot.
    $project = Project::factory()->create(['inspector_id' => $inspector->id]);
    $phase = Phase::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create(['phase_id' => $phase->id]);

    expect($project->users()->where('user_id', $inspector->id)->exists())->toBeFalse()
        ->and($inspector->can('inspect', $task))->toBeTrue();
});

it('forbids an unrelated inspector from inspecting another project\'s tasks', function () {
    $inspector = userAs('inspector');
    $project = Project::factory()->create(); // different inspector_id (the manager), not this one
    $phase = Phase::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create(['phase_id' => $phase->id]);

    expect($inspector->can('inspect', $task))->toBeFalse();
});
