<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
    }

    /**
     * Determine whether the user can see the main dashboard.
     */
    public function viewDashboard(User $user): bool
    {
        return ! $user->isWorker() && ! $user->isClient() && ! $user->isInspector();
    }

    /**
     * Determine whether the user can see the projects section.
     */
    public function viewProjects(User $user): bool
    {
        return $user->hasPermissionTo('view projects');
    }

    /**
     * Determine whether the user can see their personal task list.
     */
    public function viewMyTasks(User $user): bool
    {
        if ($user->isClient()) {
            return false;
        }

        return $user->hasPermissionTo('view tasks');
    }

    /**
     * Determine whether the user can see the materials section.
     */
    public function viewMaterials(User $user): bool
    {
        if ($user->isClient()) {
            return false;
        }

        return $user->hasPermissionTo('view materials');
    }

    /**
     * Determine whether the user can see the material requests section.
     */
    public function viewMaterialRequests(User $user): bool
    {
        if ($user->isClient()) {
            return false;
        }

        return $user->hasPermissionTo('create material requests');
    }

    /**
     * Determine whether the user can see the inspection section.
     */
    public function inspectTasks(User $user): bool
    {
        if ($user->isClient()) {
            return false;
        }

        return $user->hasPermissionTo('inspect tasks');
    }

    /**
     * Determine whether the user can see the team members section.
     */
    public function viewTeamMembers(User $user): bool
    {
        if ($user->isClient()) {
            return false;
        }

        return $user->hasPermissionTo('view team members');
    }

    /**
     * Determine whether the user can create/edit/delete team members.
     */
    public function manageTeamMembers(User $user): bool
    {
        return $user->hasPermissionTo('manage team members');
    }

    /**
     * Determine whether the user can clock time in/out (self-service).
     */
    public function createTimeEntries(User $user): bool
    {
        return ! $user->isClient();
    }

    /**
     * Determine whether the user can administer timesheets.
     */
    public function manageTimeTracking(User $user): bool
    {
        return $user->canManageTimeTracking();
    }

    /**
     * Determine whether the user can submit leave requests (self-service).
     */
    public function createLeaveRequests(User $user): bool
    {
        return ! $user->isClient();
    }

    /**
     * Determine whether the user can approve/reject leave requests.
     */
    public function approveLeave(User $user): bool
    {
        return $user->canApproveLeave();
    }
}
