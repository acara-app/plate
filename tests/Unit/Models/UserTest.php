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
            'timezone',
            'is_verified',
            'settings',
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

test('is_verified returns false when database value is null', function (): void {
    $user = User::factory()->create(['is_verified' => null]);

    expect($user->is_verified)->toBeFalse();
});

test('is_verified returns false when database value is false', function (): void {
    $user = User::factory()->create(['is_verified' => false]);

    expect($user->is_verified)->toBeFalse();
});

test('is_verified returns true when database value is true', function (): void {
    $user = User::factory()->verified()->create();

    expect($user->is_verified)->toBeTrue();
});

test('is_verified returns true for admin emails', function (): void {
    config(['sponsors.admin_emails' => ['admin@example.com']]);

    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'is_verified' => false,
    ]);

    expect($user->is_verified)->toBeTrue();
});

test('is_verified returns true when user has active subscription', function (): void {
    $user = User::factory()->create(['is_verified' => false]);

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

    expect($user->fresh()->is_verified)->toBeTrue();
});

test('prunable returns users without verified email older than 30 days', function (): void {
    $oldUnverifiedUser = User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subDays(31),
    ]);

    User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subDays(29),
    ]);

    User::factory()->create([
        'email_verified_at' => now()->subDays(31),
        'created_at' => now()->subDays(31),
    ]);

    $prunableUsers = new User()->prunable()->get();

    expect($prunableUsers)
        ->toHaveCount(1)
        ->first()->id->toBe($oldUnverifiedUser->id);
});

test('prunable method returns correct query builder instance', function (): void {
    $user = new User();

    $prunableQuery = $user->prunable();

    expect($prunableQuery)->toBeInstanceOf(Illuminate\Database\Eloquent\Builder::class);
});

test('prunable filters out verified users', function (): void {
    User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subDays(31),
    ]);

    User::factory()->create([
        'email_verified_at' => now()->subDay(),
        'created_at' => now()->subDays(31),
    ]);

    $prunableUsers = new User()->prunable()->get();

    expect($prunableUsers)->toHaveCount(1);
});

test('prunable filters out recent unverified users', function (): void {

    $recentUser = User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subDays(29),
    ]);

    $oldUser = User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subDays(31),
    ]);

    $prunableUsers = new User()->prunable()->get();

    $ourPrunableUsers = $prunableUsers->whereIn('id', [$recentUser->id, $oldUser->id]);

    expect($ourPrunableUsers)
        ->toHaveCount(1)
        ->first()->id->toBe($oldUser->id);
});
