<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('to array', function (): void {
    $user = User::factory()->create()->refresh();

    expect(array_keys($user->toArray()))
        ->toBe([
            'id',
            'name',
            'email',
            'email_verified_at',
            'two_factor_confirmed_at',
            'created_at',
            'updated_at',
            'google_id',
            'stripe_id',
            'pm_type',
            'pm_last_four',
            'trial_ends_at',
            'is_onboarded',
            'has_meal_plan',
            'profile',
        ]);
});

test('has active subscription returns false when no subscription', function (): void {
    $user = User::factory()->create();

    expect($user->hasActiveSubscription())->toBeFalse();
});

test('active subscription returns null when no subscription', function (): void {
    $user = User::factory()->create();

    expect($user->activeSubscription())->toBeNull();
});

test('subscription display name returns null when no subscription', function (): void {
    $user = User::factory()->create();

    expect($user->subscriptionDisplayName())->toBeNull();
});

test('subscription display name returns formatted name when subscription exists', function (): void {
    $user = User::factory()->create();

    // Create a subscription directly in the database
    DB::table('subscriptions')->insert([
        'user_id' => $user->id,
        'type' => 'premium-plan',
        'stripe_id' => 'sub_test123',
        'stripe_status' => 'active',
        'stripe_price' => 'price_test123',
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect($user->fresh()->subscriptionDisplayName())->toBe('Premium Plan');
});
