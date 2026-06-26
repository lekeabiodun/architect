<?php

use App\Livewire\TimeTracking\LeaveApproval;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

function pendingLeaveRequest(User $worker, int $businessDays = 3): LeaveRequest
{
    // Monday + (n-1) days keeps the span inside one work week (no weekends).
    $start = now()->next(Carbon::MONDAY)->startOfDay();
    $end = $start->copy()->addDays($businessDays - 1);

    return LeaveRequest::create([
        'user_id' => $worker->id,
        'leave_type' => 'vacation',
        'start_date' => $start->toDateString(),
        'end_date' => $end->toDateString(),
        'reason' => 'Holiday',
        'status' => 'pending',
    ]);
}

function vacationBalance(User $worker, float $balanceDays, int $year): LeaveBalance
{
    return LeaveBalance::create([
        'user_id' => $worker->id,
        'leave_type' => 'vacation',
        'year' => $year,
        'balance_days' => $balanceDays,
        'used_days' => 0,
    ]);
}

it('deducts the leave balance when a request is approved', function () {
    $worker = User::factory()->create();
    $worker->assignRole('worker');
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $request = pendingLeaveRequest($worker, businessDays: 3);
    expect((float) $request->duration_days)->toBe(3.0);

    $balance = vacationBalance($worker, balanceDays: 21, year: $request->start_date->year);

    actingAs($manager);

    Livewire::test(LeaveApproval::class)
        ->call('openApproveModal', $request->id)
        ->call('approveRequest')
        ->assertHasNoErrors();

    expect($request->fresh()->status)->toBe('approved');

    $balance->refresh();
    expect((float) $balance->used_days)->toBe(3.0)
        ->and((float) $balance->available_days)->toBe(18.0);
});

it('blocks approval and leaves the balance untouched when there are not enough days', function () {
    $worker = User::factory()->create();
    $worker->assignRole('worker');
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $request = pendingLeaveRequest($worker, businessDays: 3);
    $balance = vacationBalance($worker, balanceDays: 2, year: $request->start_date->year);

    actingAs($manager);

    Livewire::test(LeaveApproval::class)
        ->call('openApproveModal', $request->id)
        ->call('approveRequest')
        ->assertHasErrors('approval_notes');

    expect($request->fresh()->status)->toBe('pending')
        ->and((float) $balance->fresh()->used_days)->toBe(0.0);
});

it('does not deduct any balance when a request is rejected', function () {
    $worker = User::factory()->create();
    $worker->assignRole('worker');
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $request = pendingLeaveRequest($worker, businessDays: 3);
    $balance = vacationBalance($worker, balanceDays: 21, year: $request->start_date->year);

    actingAs($manager);

    Livewire::test(LeaveApproval::class)
        ->call('openRejectModal', $request->id)
        ->set('rejection_reason', 'Project deadline this week')
        ->call('rejectRequest')
        ->assertHasNoErrors();

    expect($request->fresh()->status)->toBe('rejected')
        ->and((float) $balance->fresh()->used_days)->toBe(0.0);
});

it('restores the leave balance when an approved request is deleted', function () {
    $worker = User::factory()->create();
    $worker->assignRole('worker');
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $request = pendingLeaveRequest($worker, businessDays: 3);
    $balance = vacationBalance($worker, balanceDays: 21, year: $request->start_date->year);

    $request->approve($manager);
    expect((float) $balance->fresh()->used_days)->toBe(3.0);

    // Deleting the approved request must return the deducted days.
    $request->delete();

    expect((float) $balance->fresh()->used_days)->toBe(0.0)
        ->and((float) $balance->fresh()->available_days)->toBe(21.0);
});

it('does not change the balance when a pending request is deleted', function () {
    $worker = User::factory()->create();
    $worker->assignRole('worker');

    $request = pendingLeaveRequest($worker, businessDays: 3);
    $balance = vacationBalance($worker, balanceDays: 21, year: $request->start_date->year);

    $request->delete();

    expect((float) $balance->fresh()->used_days)->toBe(0.0);
});

it('forbids a user from approving their own leave request', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $request = pendingLeaveRequest($manager, businessDays: 2);
    vacationBalance($manager, balanceDays: 21, year: $request->start_date->year);

    actingAs($manager);

    Livewire::test(LeaveApproval::class)
        ->call('openApproveModal', $request->id)
        ->assertForbidden();
});
