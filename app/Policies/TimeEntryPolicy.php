<?php

namespace App\Policies;

use App\Models\TimeEntry;
use App\Models\User;

class TimeEntryPolicy
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
        // Viewing everyone's entries is part of the timesheet-admin capability.
        // (Users see their own entries through the clock-in screen, not here.)
        return $user->canManageTimeTracking();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TimeEntry $timeEntry): bool
    {
        // Users can view their own time entries
        if ($timeEntry->user_id === $user->id) {
            return true;
        }

        // Admins can view all time entries
        return $user->canManageTimeTracking();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated staff may create their own time entries;
        // clients don't track time.
        return ! $user->isClient();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TimeEntry $timeEntry): bool
    {
        // Users can only update their own entries
        if ($timeEntry->user_id === $user->id) {
            // Allow updating only if not clocked out yet (for notes, project, etc.)
            return ! $timeEntry->clock_out || $user->canManageTimeTracking();
        }

        // Admins can update any time entry
        return $user->canManageTimeTracking();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TimeEntry $timeEntry): bool
    {
        // Users can only delete their own entries and only if they're recent (within 24 hours)
        if ($timeEntry->user_id === $user->id) {
            return $timeEntry->created_at->diffInHours(now()) <= 24;
        }

        // Admins can delete any time entry
        return $user->canManageTimeTracking();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TimeEntry $timeEntry): bool
    {
        return $user->canManageTimeTracking();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TimeEntry $timeEntry): bool
    {
        return $user->canManageTimeTracking();
    }

    /**
     * Determine whether the user can clock in.
     */
    public function clockIn(User $user): bool
    {
        // Clients don't track time; the "active entry" guard lives in the component.
        return ! $user->isClient();
    }

    /**
     * Determine whether the user can clock out.
     */
    public function clockOut(User $user, TimeEntry $timeEntry): bool
    {
        // Users may clock out their own entry; admins may clock out anyone's.
        return $timeEntry->user_id === $user->id || $user->canManageTimeTracking();
    }

    /**
     * Determine whether the user can edit time entries (admin function).
     */
    public function editTimeEntry(User $user): bool
    {
        return $user->canManageTimeTracking();
    }
}
