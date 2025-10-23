<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
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
        return $user->hasPermissionTo('view tasks');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        // Super admin can view all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Workers can only view tasks assigned to them
        if ($user->isWorker()) {
            return $task->assigned_to === $user->id;
        }

        // Get the project through phase relationship
        $project = $task->phase->project;

        // Check if user is assigned to the project or is the manager
        return $project->users()->where('user_id', $user->id)->exists() ||
            $project->manager_id === $user->id ||
            $task->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create tasks');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        // Super admin can update all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Workers cannot edit tasks
        if ($user->isWorker()) {
            return false;
        }

        $project = $task->phase->project;

        // Project manager can update
        if ($project->manager_id === $user->id) {
            return true;
        }

        // Check permission and project assignment
        return $user->hasPermissionTo('edit tasks') &&
            $project->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        $project = $task->phase->project;

        return $user->isSuperAdmin() ||
            ($user->hasPermissionTo('delete tasks') && $project->manager_id === $user->id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can complete the task.
     */
    public function complete(User $user, Task $task): bool
    {
        // Super admin can complete any task
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User must be assigned to the task or be able to manage it
        return $task->assigned_to === $user->id || $this->update($user, $task);
    }

    /**
     * Determine whether the user can inspect the task.
     */
    public function inspect(User $user, Task $task): bool
    {
        // Must have inspection permission
        if (!$user->canInspectTasks()) {
            return false;
        }

        // Inspector must be assigned to the project
        $project = $task->phase->project;

        return $user->isSuperAdmin() ||
            $project->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can add comments/media to the task.
     */
    public function addMedia(User $user, Task $task): bool
    {
        // Workers can add media to their tasks
        if ($user->isWorker() && $task->assigned_to === $user->id) {
            return true;
        }

        return $this->view($user, $task) && $user->hasPermissionTo('upload documents');
    }
}
