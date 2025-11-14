<?php

use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

// Route::get('/', function () {
//     return view('welcome');
// })->name('home');

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

Route::redirect('/', 'dashboard');
Route::group([
    'middleware' => ['auth', 'verified'],
], function () {
    Route::get('/', Dashboard::class)->name('dashboard.index');

    // Projects
    Route::get('/projects', App\Livewire\Project\Index::class)->name('projects.index');
    Route::get('/projects/{id}', App\Livewire\Project\Show::class)->name('projects.show');
    Route::get('/projects/{id}/budget', App\Livewire\Project\Budget::class)->name('projects.budget');
    Route::get('/projects/{id}/bill-of-quantities', App\Livewire\Project\BillOfQuantities::class)->name('projects.bill-of-quantities');

    // Materials & Inventory
    // Route::get('/materials', App\Livewire\Material\Index::class)->name('materials.index');
    Route::get('/material-requests', App\Livewire\Material\MaterialRequest::class)->name('material-requests.index');

    // Inspector
    Route::get('/inspector/dashboard', App\Livewire\Inspector\Dashboard::class)->name('inspector.dashboard');
    Route::get('/inspector/tasks', App\Livewire\Task\InspectorView::class)->name('inspector.tasks');

    // Worker Tasks
    Route::get('/my-tasks', App\Livewire\Task\WorkerView::class)->name('tasks.my-tasks');

    // Team
    Route::get('/team', App\Livewire\Team\Index::class)->name('team.index');
});

// Super Admin Routes
Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureSuperAdmin::class])->group(function () {
    Route::get('/settings/roles-permissions', App\Livewire\Settings\RolesAndPermissions::class)->name('settings.roles-permissions');
    Route::get('/settings/user-roles', App\Livewire\Settings\UserRoleManagement::class)->name('settings.user-roles');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

require __DIR__ . '/auth.php';
