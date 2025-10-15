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
    // 'prefix' => 'dashboard',
    // 'as' => 'dashboard.',
], function () {
    Route::get('/', Dashboard::class)->name('dashboard.index');
    Route::get('/projects', App\Livewire\Project\Index::class)->name('projects.index');
    Route::get('/projects/{id}', App\Livewire\Project\Show::class)->name('projects.show');
    Route::get('/team', App\Livewire\Team\Index::class)->name('team.index');
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
