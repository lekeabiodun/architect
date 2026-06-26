<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Viewing everyone's requests is part of the leave-approval capability.
        // (Users see their own requests on the leave-request screen, not here.)
        return $user->canApproveLeave();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        // Users can view their own leave requests
        if ($leaveRequest->user_id === $user->id) {
            return true;
        }

        // Users who can approve leave can view all requests
        return $user->canApproveLeave();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated staff may submit their own leave requests;
        // clients don't request leave. Approval is gated separately.
        return ! $user->isClient();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        // Users can only update their own pending requests
        if ($leaveRequest->user_id === $user->id) {
            return $leaveRequest->isPending();
        }

        // Admins can update any leave request
        return $user->canApproveLeave();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        // Users can only delete their own pending requests
        if ($leaveRequest->user_id === $user->id) {
            return $leaveRequest->isPending();
        }

        // Admins can delete any leave request
        return $user->canApproveLeave();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->canApproveLeave();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->canApproveLeave();
    }

    /**
     * Determine whether the user can approve the leave request.
     */
    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        // Must be pending to be approved
        if (! $leaveRequest->isPending()) {
            return false;
        }

        // Users can't approve their own leave requests
        if ($leaveRequest->user_id === $user->id) {
            return false;
        }

        return $user->canApproveLeave();
    }

    /**
     * Determine whether the user can reject the leave request.
     */
    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->approve($user, $leaveRequest);
    }

    /**
     * Determine whether the user can approve/reject leave requests in general.
     *
     * This is the capability check for bulk actions, where there is no single
     * request to authorize. Per-request rules (pending, not own request) are
     * still enforced via the approve() ability for each request.
     */
    public function approveAny(User $user): bool
    {
        return $user->canApproveLeave();
    }

    /**
     * Determine whether the user can manage leave balances.
     */
    public function manageLeaveBalances(User $user): bool
    {
        return $user->canApproveLeave();
    }

    /**
     * Determine whether the user can view leave calendar.
     */
    public function viewLeaveCalendar(User $user): bool
    {
        // All non-client users can view the leave calendar.
        return ! $user->isClient();
    }
}
