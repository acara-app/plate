<?php

declare(strict_types=1);

use App\Http\Controllers as Web;
use App\Livewire\SnapToTrack;
use App\Livewire\SpikeCalculator;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::view('/', 'welcome')->name('home');
Route::view('/privacy-policy', 'privacy-policy')->name('privacy');
Route::view('/terms-of-service', 'terms-of-service')->name('terms');
Route::view('/support', 'support')->name('support');
Route::view('/install-app', 'install-app')->name('install-app');

Route::view('/diabetes-log-book', 'diabetes-log-book')->name('diabetes-log-book');
Route::view('/diabetes-log-book-info', 'diabetes-log-book-info')->name('diabetes-log-book-info');
Route::get('/spike-calculator', SpikeCalculator::class)->name('spike-calculator');
Route::get('/snap-to-track', SnapToTrack::class)->name('snap-to-track');
Route::view('/10-day-meal-plan', '10-day-meal-plan')->name('10-day-meal-plan');

Route::get('/food', [Web\PublicFoodController::class, 'index'])->name('food.index');
Route::get('/food/category/{category}', [Web\PublicFoodController::class, 'category'])->name('food.category');
Route::get('/food/{slug}', [Web\PublicFoodController::class, 'show'])->name('food.show');

Route::post('/profile/timezone', [Web\UserTimezoneController::class, 'update'])->name('profile.timezone.update');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', [Web\DashboardController::class, 'show'])->name('dashboard');

    Route::get('meal-plans', Web\ShowMealPlansController::class)->name('meal-plans.index');
    Route::get('meal-plans/{mealPlan}/print', Web\PrintMealPlanController::class)->name('meal-plans.print');
    Route::post('meal-plans/{mealPlan}/generate-day', Web\GenerateMealDayController::class)->name('meal-plans.generate-day');
    Route::post('meal-plans/{mealPlan}/regenerate-day', Web\RegenerateMealPlanDayController::class)->name('meal-plans.regenerate-day');
    Route::post('meal-plans/regenerate', [Web\RegenerateMealPlanController::class, 'store'])->name('meal-plans.regenerate');

    // Grocery List...
    Route::get('meal-plans/{mealPlan}/grocery-list', [Web\GroceryListController::class, 'show'])->name('meal-plans.grocery-list.show');
    Route::post('meal-plans/{mealPlan}/grocery-list', [Web\GroceryListController::class, 'store'])->name('meal-plans.grocery-list.store');
    Route::get('meal-plans/{mealPlan}/grocery-list/print', Web\PrintGroceryListController::class)->name('meal-plans.grocery-list.print');
    Route::patch('grocery-items/{groceryItem}/toggle', [Web\GroceryListController::class, 'toggleItem'])->name('grocery-items.toggle');

    Route::get('chat/create', [Web\ChatController::class, 'create'])->name('chat.create');

    Route::get('ongoing-tracking/food-log/create', [Web\FoodLogController::class, 'create'])->name('food-log.create');

    Route::get('diabetes-log', Web\Diabetes\ListDiabetesLogController::class)->name('diabetes-log.index');
    Route::get('diabetes-log/tracking', Web\Diabetes\DashboardDiabetesLogController::class)->name('diabetes-log.dashboard');
    Route::get('diabetes-log/insights', Web\Diabetes\InsightsDiabetesLogController::class)->name('diabetes-log.insights');
    Route::post('diabetes-log', Web\Diabetes\StoreDiabetesLogController::class)->name('diabetes-log.store');
    Route::put('diabetes-log/{diabetesLog}', Web\Diabetes\UpdateDiabetesLogController::class)->name('diabetes-log.update');
    Route::delete('diabetes-log/{diabetesLog}', Web\Diabetes\DestroyDiabetesLogController::class)->name('diabetes-log.destroy');
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

    // User Notifications...
    Route::get('settings/notifications', [Web\UserNotificationsController::class, 'edit'])->name('user-notifications.edit');
    Route::patch('settings/notifications', [Web\UserNotificationsController::class, 'update'])->name('user-notifications.update');

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
