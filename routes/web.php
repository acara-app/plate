<?php

declare(strict_types=1);

use App\Http\Controllers as Web;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::view('/', 'welcome')->name('home');
Route::view('/privacy-policy', 'privacy-policy')->name('privacy');
Route::view('/terms-of-service', 'terms-of-service')->name('terms');
Route::view('/support', 'support')->name('support');
Route::view('/install-app', 'install-app')->name('install-app');

Route::post('/profile/timezone', [Web\UserTimezoneController::class, 'update'])->name('profile.timezone.update');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', [Web\DashboardController::class, 'show'])->name('dashboard');

    Route::get('meal-plans', Web\ShowMealPlansController::class)->name('meal-plans.index');
    Route::post('meal-plans/{mealPlan}/generate-day', Web\GenerateMealDayController::class)->name('meal-plans.generate-day');

    Route::get('chat/create', [Web\ChatController::class, 'create'])->name('chat.create');

    Route::get('ongoing-tracking/food-log/create', [Web\FoodLogController::class, 'create'])->name('food-log.create');

    // Glucose Tracking...
    Route::get('glucose', [Web\GlucoseReadingController::class, 'index'])->name('glucose.index');
    Route::get('glucose/tracking', [Web\GlucoseReadingController::class, 'dashboard'])->name('glucose.dashboard');
    Route::post('glucose', [Web\GlucoseReadingController::class, 'store'])->name('glucose.store');
    Route::put('glucose/{glucoseReading}', [Web\GlucoseReadingController::class, 'update'])->name('glucose.update');
    Route::delete('glucose/{glucoseReading}', [Web\GlucoseReadingController::class, 'destroy'])->name('glucose.destroy');
});

Route::middleware(['auth'])->prefix('onboarding')->name('onboarding.')->group(function (): void {
    Route::get('/', [Web\OnboardingController::class, 'showQuestionnaire'])->name('questionnaire.show');

    Route::get('/biometrics', [Web\OnboardingController::class, 'showBiometrics'])->name('biometrics.show');
    Route::post('/biometrics', [Web\OnboardingController::class, 'storeBiometrics'])->name('biometrics.store');

    Route::get('/goals', [Web\OnboardingController::class, 'showGoals'])->name('goals.show');
    Route::post('/goals', [Web\OnboardingController::class, 'storeGoals'])->name('goals.store');

    Route::get('/lifestyle', [Web\OnboardingController::class, 'showLifestyle'])->name('lifestyle.show');
    Route::post('/lifestyle', [Web\OnboardingController::class, 'storeLifestyle'])->name('lifestyle.store');

    Route::get('/dietary-preferences', [Web\OnboardingController::class, 'showDietaryPreferences'])->name('dietary-preferences.show');
    Route::post('/dietary-preferences', [Web\OnboardingController::class, 'storeDietaryPreferences'])->name('dietary-preferences.store');

    Route::get('/health-conditions', [Web\OnboardingController::class, 'showHealthConditions'])->name('health-conditions.show');
    Route::post('/health-conditions', [Web\OnboardingController::class, 'storeHealthConditions'])->name('health-conditions.store');

    Route::get('/completion', [Web\OnboardingController::class, 'showCompletion'])->name('completion.show');

});

Route::middleware('auth')->group(function (): void {
    // User...
    Route::delete('user', [Web\UserController::class, 'destroy'])->name('user.destroy');

    // User Profile...
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [Web\UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('settings/profile', [Web\UserProfileController::class, 'update'])->name('user-profile.update');

    // Billing History...
    Route::get('settings/billing', [Web\BillingHistoryController::class, 'index'])->name('billing.index');

    // User Password...
    Route::get('settings/password', [Web\UserPasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [Web\UserPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    // Appearance...
    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))->name('appearance.edit');

    // User Two-Factor Authentication...
    Route::get('settings/two-factor', [Web\UserTwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    // User Subscription Management...
    Route::get('/checkout/subscription', Web\Checkout\CashierShowSubscriptionController::class)
        ->name('checkout.subscription');
    Route::post('/checkout/subscription', Web\Checkout\CashierSubscriptionController::class)
        ->name('checkout.subscription.store');

    Route::get('/checkout/success', Web\Checkout\CashierShowSubscriptionController::class)
        ->name('checkout.success');
    Route::get('/checkout/cancel', Web\Checkout\CashierShowSubscriptionController::class)
        ->name('checkout.cancel');

    Route::get('/billing-portal', function (Illuminate\Http\Request $request) {
        $user = $request->user();

        abort_if($user === null, 401);

        return $user->redirectToBillingPortal(route('checkout.subscription'));
    })->name('billing.portal');
});

Route::middleware('guest')->group(function (): void {
    // User...
    Route::get('register', [Web\UserController::class, 'create'])
        ->name('register');
    Route::post('register', [Web\UserController::class, 'store'])
        ->name('register.store');

    // User Password...
    Route::get('reset-password/{token}', [Web\UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [Web\UserPasswordController::class, 'store'])
        ->name('password.store');

    // User Email Reset Notification...
    Route::get('forgot-password', [Web\UserEmailResetNotification::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [Web\UserEmailResetNotification::class, 'store'])
        ->name('password.email');

    // Session...
    Route::get('login', [Web\Auth\SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [Web\Auth\SessionController::class, 'store'])
        ->name('login.store');

    // Socialite Authentication...
    Route::get('/auth/google/redirect', [Web\Auth\SocialiteController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [Web\Auth\SocialiteController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function (): void {
    // User Email Verification...
    Route::get('verify-email', [Web\UserEmailVerificationNotificationController::class, 'create'])
        ->name('verification.notice');
    Route::post('email/verification-notification', [Web\UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // User Email Verification...
    Route::get('verify-email/{id}/{hash}', [Web\UserEmailVerification::class, 'update'])
        ->middleware(['signed:relative', 'throttle:6,1'])
        ->name('verification.verify');

    // Session...
    Route::post('logout', [Web\Auth\SessionController::class, 'destroy'])
        ->name('logout');
});
