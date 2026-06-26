<?php

namespace App\Policies;

use App\Models\BillOfQuantity;
use App\Models\Project;
use App\Models\User;

class BillOfQuantityPolicy
{
    /**
     * Super admin bypasses all checks.
     */
    public function before(User $user, $ability)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any BOQ items.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view boq');
    }

    /**
     * Determine whether the user can view the BOQ.
     */
    public function view(User $user, BillOfQuantity $billOfQuantity): bool
    {
        // Must have permission
        if (! $user->hasPermissionTo('view boq')) {
            return false;
        }

        // Clients and workers cannot view BOQ
        if ($user->isClient() || $user->isWorker()) {
            return false;
        }

        // Must be assigned to the project or be the manager/inspector
        $project = $billOfQuantity->project;

        return $project->users()->where('user_id', $user->id)->exists() ||
            $project->manager_id === $user->id ||
            $project->inspector_id === $user->id;
    }

    /**
     * Determine whether the user can view BOQ for a specific project.
     */
    public function viewForProject(User $user, Project $project): bool
    {
        // Must have permission
        if (! $user->hasPermissionTo('view boq')) {
            return false;
        }

        // Clients and workers cannot view BOQ
        if ($user->isClient() || $user->isWorker()) {
            return false;
        }

        // Must be assigned to the project or be the manager/inspector
        return $project->users()->where('user_id', $user->id)->exists() ||
            $project->manager_id === $user->id ||
            $project->inspector_id === $user->id;
    }

    /**
     * Determine whether the user can create BOQ items.
     */
    public function create(User $user, Project $project): bool
    {
        // Must have permission
        if (! $user->hasPermissionTo('create boq')) {
            return false;
        }

        // Must be project manager or assigned to the project
        return $project->manager_id === $user->id ||
            ($project->users()->where('user_id', $user->id)->exists() &&
             $user->hasAnyRole(['project_manager', 'director', 'manager']));
    }

    /**
     * Determine whether the user can update the BOQ.
     */
    public function update(User $user, BillOfQuantity $billOfQuantity): bool
    {
        // Must have permission
        if (! $user->hasPermissionTo('edit boq')) {
            return false;
        }

        $project = $billOfQuantity->project;

        // Project manager can always edit
        if ($project->manager_id === $user->id) {
            return true;
        }

        // Check permission and project assignment
        return $project->users()->where('user_id', $user->id)->exists() &&
            $user->hasAnyRole(['project_manager', 'director', 'manager']);
    }

    /**
     * Determine whether the user can delete the BOQ.
     */
    public function delete(User $user, BillOfQuantity $billOfQuantity): bool
    {
        // Must have permission
        if (! $user->hasPermissionTo('delete boq')) {
            return false;
        }

        $project = $billOfQuantity->project;

        // Project manager can always delete
        if ($project->manager_id === $user->id) {
            return true;
        }

        // Directors and managers assigned to the project can delete
        return $project->users()->where('user_id', $user->id)->exists() &&
            $user->hasAnyRole(['project_manager', 'director', 'manager']);
    }

    /**
     * Determine whether the user can restore the BOQ.
     */
    public function restore(User $user, BillOfQuantity $billOfQuantity): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the BOQ.
     */
    public function forceDelete(User $user, BillOfQuantity $billOfQuantity): bool
    {
        return $user->isSuperAdmin();
    }
}
