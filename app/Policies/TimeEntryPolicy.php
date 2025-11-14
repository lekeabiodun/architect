<?php

namespace App\Policies;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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
        // Users who can manage time tracking can view all entries
        // Regular users can only view their own entries
        return $user->canManageTimeTracking() || $user->hasPermissionTo('view time entries');
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
        // All authenticated users can create their own time entries
        // except clients who typically don't track time
        return !$user->isClient() && $user->hasPermissionTo('create time entries');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TimeEntry $timeEntry): bool
    {
        // Users can only update their own entries
        if ($timeEntry->user_id === $user->id) {
            // Allow updating only if not clocked out yet (for notes, project, etc.)
            return !$timeEntry->clock_out || $user->canManageTimeTracking();
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
        return true;
        // Users can clock in if they don't have an active entry
        // and they're not clients
        if ($user->isClient()) {
            return false;
        }

        return !TimeEntry::getActiveForUser($user->id) && $user->hasPermissionTo('clock in');
    }

    /**
     * Determine whether the user can clock out.
     */
    public function clockOut(User $user, TimeEntry $timeEntry): bool
    {
        return true;
        // Users can only clock out their own active entries
        if ($timeEntry->user_id !== $user->id) {
            return $user->canManageTimeTracking();
        }

        return $timeEntry->isActive() && $user->hasPermissionTo('clock out');
    }

    /**
     * Determine whether the user can edit time entries (admin function).
     */
    public function editTimeEntry(User $user): bool
    {
        return $user->canManageTimeTracking();
    }
}
