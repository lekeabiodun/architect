<?php

namespace App\Providers;

use App\Models\BillOfQuantity;
use App\Models\Inventory;
use App\Models\MaterialRequest;
use App\Models\Project;
use App\Models\Task;
use App\Policies\BillOfQuantityPolicy;
use App\Policies\InventoryPolicy;
use App\Policies\MaterialRequestPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Project::class => ProjectPolicy::class,
        Task::class => TaskPolicy::class,
        MaterialRequest::class => MaterialRequestPolicy::class,
        Inventory::class => InventoryPolicy::class,
        BillOfQuantity::class => BillOfQuantityPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Super admin bypass - they can do everything
        Gate::before(function ($user, $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        // Define gates for admin features
        Gate::define('manage-roles', function ($user) {
            return $user->isSuperAdmin();
        });

        Gate::define('manage-permissions', function ($user) {
            return $user->isSuperAdmin();
        });

        Gate::define('assign-roles', function ($user) {
            return $user->isSuperAdmin();
        });
    }
}
