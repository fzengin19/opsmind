<?php

use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');


// Google OAuth routes
Route::get('/auth/google', [SocialiteController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [SocialiteController::class, 'callback']);

// Invitation accept (public route)
Volt::route('/invitation/{token}', 'invitation.accept')->name('invitation.accept');

Volt::route('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'ensure.has.company'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Onboarding
    Volt::route('onboarding/create-company', 'onboarding.create-company')
        ->name('onboarding.create-company');

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
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

    // Role Management
    Route::middleware(['ensure.has.company'])->group(function () {
        Volt::route('settings/roles', 'settings.roles.index')->name('settings.roles.index');
        Volt::route('settings/roles/create', 'settings.roles.form')->name('settings.roles.create');
        Volt::route('settings/roles/{role}/edit', 'settings.roles.form')->name('settings.roles.edit');
    });

    // Team management (requires company)
    Volt::route('team', 'team.index')
        ->middleware(['ensure.has.company'])
        ->name('team.index');


    // Calendar (requires company)
    Volt::route('calendar', 'calendar.index')
        ->middleware(['ensure.has.company'])
        ->name('calendar.index');
});
