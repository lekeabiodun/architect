<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
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
        // All authenticated users can view projects
        return $user->hasPermissionTo('view projects');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        // Super admin can view all projects
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Clients can only view their own projects
        if ($user->isClient()) {
            return $project->users()->where('user_id', $user->id)->exists() ||
                   ($project->client && $project->client->user_id === $user->id);
        }

        // Workers cannot view full project details
        if ($user->isWorker()) {
            return false;
        }

        // Others can view if they're assigned to the project, are the manager, or are the inspector
        return $project->users()->where('user_id', $user->id)->exists() ||
            $project->manager_id === $user->id ||
            $project->inspector_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create projects');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        // Super admin can update all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Project manager can update their projects
        if ($project->manager_id === $user->id) {
            return true;
        }

        // Check permission and project assignment
        return $user->hasPermissionTo('edit projects') &&
            $project->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->isSuperAdmin() ||
            ($user->hasPermissionTo('delete projects') && $project->manager_id === $user->id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view materials for this project.
     */
    public function viewMaterials(User $user, Project $project): bool
    {
        // Clients and workers cannot view materials
        if ($user->isClient() || $user->isWorker()) {
            return false;
        }

        // Must be able to view project and have material permission
        return $this->view($user, $project) && $user->canViewMaterials();
    }

    /**
     * Determine whether the user can manage materials for this project.
     */
    public function manageMaterials(User $user, Project $project): bool
    {
        return $this->view($user, $project) && $user->canManageMaterials();
    }

    /**
     * Determine whether the user can view budget for this project.
     */
    public function viewBudget(User $user, Project $project): bool
    {
        // Clients cannot view budget details
        if ($user->isClient() || $user->isWorker()) {
            return false;
        }

        return $this->view($user, $project) && $user->hasPermissionTo('view budget');
    }

    /**
     * Determine whether the user can manage team members for this project.
     */
    public function manageTeam(User $user, Project $project): bool
    {
        // Super admin can manage all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Project manager can manage their project team
        if ($project->manager_id === $user->id) {
            return true;
        }

        // Check permission and project assignment
        return $user->hasPermissionTo('edit projects') &&
            $project->users()->where('user_id', $user->id)->exists();
    }
}
