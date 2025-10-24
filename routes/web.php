<?php

declare(strict_types=1);

use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\ShowWeeklyMeanPlansController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotification;
use App\Http\Controllers\UserEmailVerification;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => view('welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');

    Route::get('meal-plans/weekly', ShowWeeklyMeanPlansController::class)->name('meal-plans.weekly');
});

Route::middleware(['auth'])->prefix('onboarding')->name('onboarding.')->group(function (): void {
    Route::get('/', [OnboardingController::class, 'showQuestionnaire'])->name('questionnaire.show');

    Route::get('/biometrics', [OnboardingController::class, 'showBiometrics'])->name('biometrics.show');
    Route::post('/biometrics', [OnboardingController::class, 'storeBiometrics'])->name('biometrics.store');

    Route::get('/goals', [OnboardingController::class, 'showGoals'])->name('goals.show');
    Route::post('/goals', [OnboardingController::class, 'storeGoals'])->name('goals.store');

    Route::get('/lifestyle', [OnboardingController::class, 'showLifestyle'])->name('lifestyle.show');
    Route::post('/lifestyle', [OnboardingController::class, 'storeLifestyle'])->name('lifestyle.store');

    Route::get('/dietary-preferences', [OnboardingController::class, 'showDietaryPreferences'])->name('dietary-preferences.show');
    Route::post('/dietary-preferences', [OnboardingController::class, 'storeDietaryPreferences'])->name('dietary-preferences.store');

    Route::get('/health-conditions', [OnboardingController::class, 'showHealthConditions'])->name('health-conditions.show');
    Route::post('/health-conditions', [OnboardingController::class, 'storeHealthConditions'])->name('health-conditions.store');

    Route::get('/completion', [OnboardingController::class, 'showCompletion'])->name('completion.show');
});

Route::middleware('auth')->group(function (): void {
    // User...
    Route::delete('user', [UserController::class, 'destroy'])->name('user.destroy');

    // User Profile...
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('settings/profile', [UserProfileController::class, 'update'])->name('user-profile.update');

    // User Password...
    Route::get('settings/password', [UserPasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [UserPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    // Appearance...
    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))->name('appearance.edit');

    // User Two-Factor Authentication...
    Route::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});

Route::middleware('guest')->group(function (): void {
    // User...
    Route::get('register', [UserController::class, 'create'])
        ->name('register');
    Route::post('register', [UserController::class, 'store'])
        ->name('register.store');

    // User Password...
    Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [UserPasswordController::class, 'store'])
        ->name('password.store');

    // User Email Reset Notification...
    Route::get('forgot-password', [UserEmailResetNotification::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [UserEmailResetNotification::class, 'store'])
        ->name('password.email');

    // Session...
    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    // User Email Verification...
    Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])
        ->name('verification.notice');
    Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // User Email Verification...
    Route::get('verify-email/{id}/{hash}', [UserEmailVerification::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Session...
    Route::post('logout', [SessionController::class, 'destroy'])
        ->name('logout');
});
