<?php

namespace App\Policies;

use App\Models\MaterialRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MaterialRequestPolicy
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
        // Clients and workers cannot view material requests
        return !$user->isClient() && !$user->isWorker();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MaterialRequest $materialRequest): bool
    {
        // Super admin can view all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Clients and workers cannot view
        if ($user->isClient() || $user->isWorker()) {
            return false;
        }

        $project = $materialRequest->project;

        // User created the request
        if ($materialRequest->requested_by === $user->id) {
            return true;
        }

        // User is assigned to the project
        return $project->users()->where('user_id', $user->id)->exists() ||
            $project->manager_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Engineers, developers, contractors, managers can request materials
        return $user->hasPermissionTo('create material requests');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MaterialRequest $materialRequest): bool
    {
        // Can only update if pending and user created it
        return $materialRequest->status === 'pending' &&
            $materialRequest->requested_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MaterialRequest $materialRequest): bool
    {
        // Can cancel own pending requests
        return ($materialRequest->status === 'pending' &&
            $materialRequest->requested_by === $user->id) ||
            $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MaterialRequest $materialRequest): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MaterialRequest $materialRequest): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can approve the material request.
     */
    public function approve(User $user, MaterialRequest $materialRequest): bool
    {
        // Must be able to approve and request must be pending
        if (!$materialRequest->canBeApproved()) {
            return false;
        }

        return $user->canApproveMaterialRequests();
    }

    /**
     * Determine whether the user can reject the material request.
     */
    public function reject(User $user, MaterialRequest $materialRequest): bool
    {
        return $this->approve($user, $materialRequest);
    }

    /**
     * Determine whether the user can disburse the material request.
     */
    public function disburse(User $user, MaterialRequest $materialRequest): bool
    {
        // Must be approved first
        if (!$materialRequest->canBeDisbursed()) {
            return false;
        }

        // Directors, managers, project managers can disburse
        return $user->hasPermissionTo('disburse materials');
    }

    /**
     * Determine whether the user can confirm the material delivery (inspector).
     */
    public function confirm(User $user, MaterialRequest $materialRequest): bool
    {
        // Must be disbursed first
        if (!$materialRequest->canBeConfirmed()) {
            return false;
        }

        // Only inspectors can confirm
        return $user->hasPermissionTo('confirm material delivery');
    }
}
