<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view tasks');
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isWorker()) {
            return $task->assigned_to === $user->id;
        }

        $project = $task->phase->project;

        return $project->users()->where('user_id', $user->id)->exists() ||
            $project->manager_id === $user->id ||
            $task->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create tasks');
    }

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

    public function delete(User $user, Task $task): bool
    {
        $project = $task->phase->project;

        return $user->isSuperAdmin() ||
            ($user->hasPermissionTo('delete tasks') && $project->manager_id === $user->id);
    }

    public function restore(User $user, Task $task): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $user->isSuperAdmin();
    }

    public function complete(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $task->assigned_to === $user->id || $this->update($user, $task);
    }

    public function inspect(User $user, Task $task): bool
    {
        if (!$user->canInspectTasks()) {
            return false;
        }

        $project = $task->phase->project;

        return $user->isSuperAdmin() ||
            $project->users()->where('user_id', $user->id)->exists();
    }

    public function addMedia(User $user, Task $task): bool
    {
        if ($user->isWorker() && $task->assigned_to === $user->id) {
            return true;
        }

        return $this->view($user, $task) && $user->hasPermissionTo('upload documents');
    }

    public function viewDashboard(User $user): bool
    {
        return !$user->isWorker() && !$user->isClient() && !$user->isInspector();
    }

    public function viewProjects(User $user): bool
    {
        return $user->hasPermissionTo('view projects');
    }

    public function viewMyTasks(User $user): bool
    {
        if ($user->isClient()) return false;

        return $user->hasPermissionTo('view tasks');
    }

    public function viewMaterials(User $user): bool
    {
        if ($user->isClient()) return false;

        return $user->hasPermissionTo('view materials');
    }

    public function viewMaterialRequests(User $user): bool
    {
        if ($user->isClient()) return false;

        return $user->hasPermissionTo('create material requests');
    }

    public function inspectTasks(User $user): bool
    {
        if ($user->isClient()) return false;

        return $user->hasPermissionTo('inspect tasks');
    }

    public function viewTeamMembers(User $user): bool
    {
        if ($user->isClient()) return false;

        return $user->hasPermissionTo('view team members');
    }
}
