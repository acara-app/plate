<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureEmailIsVerifiedOrSubscribed;
use App\Models\User;
use Laravel\Cashier\Subscription;

covers(EnsureEmailIsVerifiedOrSubscribed::class);

it('lets verified users and paying subscribers into the app', function (User $user): void {
    $this->actingAs($user)
        ->get(route('snap-to-track.index'))
        ->assertOk();
})->with([
    'verified user' => fn (): User => User::factory()->create(),
    'unverified subscriber' => fn (): User => User::factory()->unverified()
        ->has(Subscription::factory()->active(), 'subscriptions')
        ->create(),
]);

it('sends unverified users without a paid subscription to verify their email', function (User $user): void {
    $this->actingAs($user)
        ->get(route('snap-to-track.index'))
        ->assertRedirect(route('verification.notice'));
})->with([
    'no subscription' => fn (): User => User::factory()->unverified()->create(),
    'payment still incomplete' => fn (): User => User::factory()->unverified()
        ->has(Subscription::factory()->incomplete(), 'subscriptions')
        ->create(),
    'subscription ended' => fn (): User => User::factory()->unverified()
        ->has(Subscription::factory()->canceled(), 'subscriptions')
        ->create(),
]);
