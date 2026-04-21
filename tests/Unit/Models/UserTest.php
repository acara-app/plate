<?php

declare(strict_types=1);

use App\Enums\ChatPlatform;
use App\Models\HealthSyncSample;
use App\Models\MobileSyncDevice;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

covers(User::class);

it('to array', function (): void {
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
            'preferred_language',
            'locale',
            'accepted_disclaimer_at',
            'is_onboarded',
            'has_meal_plan',
            'profile',
        ]);
});

it('has active subscription returns false when no subscription', function (): void {
    $user = User::factory()->create();

    expect($user->hasActiveSubscription())->toBeFalse();
});

it('active subscription returns null when no subscription', function (): void {
    $user = User::factory()->create();

    expect($user->activeSubscription())->toBeNull();
});

it('subscription display name returns null when no subscription', function (): void {
    $user = User::factory()->create();

    expect($user->subscriptionDisplayName())->toBeNull();
});

it('subscription display name returns formatted name when subscription exists', function (): void {
    $user = User::factory()->create();

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

it('is_verified returns false when database value is null', function (): void {
    $user = User::factory()->create(['is_verified' => null]);

    expect($user->is_verified)->toBeFalse();
});

it('is_verified returns true when database value is null but user has trialing subscription', function (): void {
    $user = User::factory()->create(['is_verified' => null]);

    DB::table('subscriptions')->insert([
        'user_id' => $user->id,
        'type' => 'premium-plan',
        'stripe_id' => 'sub_trial_null123',
        'stripe_status' => 'trialing',
        'stripe_price' => 'price_test123',
        'quantity' => 1,
        'trial_ends_at' => now()->addDays(7),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect($user->fresh()->is_verified)->toBeTrue();
});

it('is_verified returns false when database value is false', function (): void {
    $user = User::factory()->create(['is_verified' => false]);

    expect($user->is_verified)->toBeFalse();
});

it('is_verified returns true when database value is true', function (): void {
    $user = User::factory()->verified()->create();

    expect($user->is_verified)->toBeTrue();
});

it('is_verified returns true for admin emails', function (): void {
    config(['sponsors.admin_emails' => ['admin@example.com']]);

    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'is_verified' => false,
    ]);

    expect($user->is_verified)->toBeTrue();
});

it('is_verified returns true when user has active subscription', function (): void {
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

it('prunable returns users without verified email older than 30 days', function (): void {
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

it('prunable method returns correct query builder instance', function (): void {
    $user = new User();

    $prunableQuery = $user->prunable();

    expect($prunableQuery)->toBeInstanceOf(Builder::class);
});

it('prunable filters out verified users', function (): void {
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

it('prunable filters out recent unverified users', function (): void {

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

it('has active subscription returns true when subscription is trialing', function (): void {
    $user = User::factory()->create();

    DB::table('subscriptions')->insert([
        'user_id' => $user->id,
        'type' => 'premium-plan',
        'stripe_id' => 'sub_trial123',
        'stripe_status' => 'trialing',
        'stripe_price' => 'price_test123',
        'quantity' => 1,
        'trial_ends_at' => now()->addDays(7),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect($user->fresh()->hasActiveSubscription())->toBeTrue();
});

it('is_verified returns true when user has trialing subscription', function (): void {
    $user = User::factory()->create(['is_verified' => false]);

    DB::table('subscriptions')->insert([
        'user_id' => $user->id,
        'type' => 'premium-plan',
        'stripe_id' => 'sub_trial123',
        'stripe_status' => 'trialing',
        'stripe_price' => 'price_test123',
        'quantity' => 1,
        'trial_ends_at' => now()->addDays(7),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect($user->fresh()->is_verified)->toBeTrue();
});

it('active subscription returns trialing subscription', function (): void {
    $user = User::factory()->create();

    DB::table('subscriptions')->insert([
        'user_id' => $user->id,
        'type' => 'premium-plan',
        'stripe_id' => 'sub_trial123',
        'stripe_status' => 'trialing',
        'stripe_price' => 'price_test123',
        'quantity' => 1,
        'trial_ends_at' => now()->addDays(7),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect($user->fresh()->activeSubscription())->not->toBeNull()
        ->and($user->fresh()->activeSubscription()->stripe_status)->toBe('trialing');
});

it('health sync samples relation returns related samples', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->create();
    $sample = HealthSyncSample::factory()->for($user)->for($device)->create();

    expect($user->healthSyncSamples)
        ->toHaveCount(1)
        ->first()->id->toBe($sample->id);
});

it('active chat platform link returns the active link for the given platform', function (): void {
    $user = User::factory()->create();

    $link = UserChatPlatformLink::factory()->linked($user)->create([
        'platform' => ChatPlatform::Telegram,
    ]);

    $activeLink = $user->activeChatPlatformLink(ChatPlatform::Telegram)->first();

    expect($activeLink)->not->toBeNull();
    expect($activeLink->id)->toBe($link->id);
});

it('preferred_language returns null when not set', function (): void {
    $user = User::factory()->create(['preferred_language' => null]);

    expect($user->preferred_language)->toBeNull();
});
