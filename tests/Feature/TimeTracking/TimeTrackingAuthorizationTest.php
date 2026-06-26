<?php

use App\Livewire\TimeTracking\AdminTimesheet;
use App\Livewire\TimeTracking\LeaveApproval;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('lets a manager open the timesheet-admin screen', function () {
    actingAs(userWithRole('manager'));

    Livewire::test(AdminTimesheet::class)->assertOk();
});

it('forbids a worker from the timesheet-admin screen', function () {
    actingAs(userWithRole('worker'));

    Livewire::test(AdminTimesheet::class)->assertForbidden();
});

it('lets a manager open the leave-approval screen', function () {
    actingAs(userWithRole('manager'));

    Livewire::test(LeaveApproval::class)->assertOk();
});

it('forbids a worker from the leave-approval screen', function () {
    actingAs(userWithRole('worker'));

    Livewire::test(LeaveApproval::class)->assertForbidden();
});

it('allows a non-client to clock in and request leave', function () {
    $worker = userWithRole('worker');

    expect($worker->can('clockIn', App\Models\TimeEntry::class))->toBeTrue()
        ->and($worker->can('create', App\Models\LeaveRequest::class))->toBeTrue();
});

it('forbids a client from clocking in or requesting leave', function () {
    $client = userWithRole('client');

    expect($client->can('clockIn', App\Models\TimeEntry::class))->toBeFalse()
        ->and($client->can('create', App\Models\LeaveRequest::class))->toBeFalse();
});

it('does not throw when a worker without time-tracking permissions evaluates the nav gates', function () {
    // Regression: these gates previously referenced non-existent permissions
    // (e.g. "manage time tracking"), which threw PermissionDoesNotExist.
    $worker = userWithRole('worker');

    expect($worker->can('manageTimeTracking', User::class))->toBeFalse()
        ->and($worker->can('approveLeave', User::class))->toBeFalse()
        ->and($worker->can('createTimeEntries', User::class))->toBeTrue()
        ->and($worker->can('createLeaveRequests', User::class))->toBeTrue();
});
