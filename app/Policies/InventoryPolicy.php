<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InventoryPolicy
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
        // Clients and workers cannot view inventory
        return $user->canViewMaterials();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Inventory $inventory): bool
    {
        // Super admin can view all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Clients and workers cannot view
        if ($user->isClient() || $user->isWorker()) {
            return false;
        }

        $project = $inventory->project;

        // User must be able to view materials and be assigned to project
        return $user->canViewMaterials() && (
            $project->users()->where('user_id', $user->id)->exists() ||
            $project->manager_id === $user->id
        );
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canManageMaterials();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Inventory $inventory): bool
    {
        // Super admin can update all
        if ($user->isSuperAdmin()) {
            return true;
        }

        $project = $inventory->project;

        // Must have manage permission and be assigned to project
        return $user->canManageMaterials() && (
            $project->users()->where('user_id', $user->id)->exists() ||
            $project->manager_id === $user->id
        );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Inventory $inventory): bool
    {
        return $this->update($user, $inventory);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Inventory $inventory): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Inventory $inventory): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can allocate materials.
     */
    public function allocate(User $user, Inventory $inventory): bool
    {
        return $this->update($user, $inventory) &&
            $user->hasPermissionTo('allocate materials');
    }
}
