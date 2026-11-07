<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureEmailIsVerifiedOrSubscribed;
use App\Models\User;
use Laravel\Cashier\Subscription;

covers(EnsureEmailIsVerifiedOrSubscribed::class);

function unverifiedSubscriber(string $state, array $attributes = []): User
{
    return User::factory()->unverified()->has(Subscription::factory()->{$state}()->state($attributes), 'subscriptions')->create();
}

function subscriptionWebhook(string $type): array
{
    return ['id' => 'evt_'.$type, 'type' => $type, 'data' => ['object' => [
        'id' => 'sub_paid_before_verifying',
        'customer' => 'cus_paid_before_verifying',
        'status' => 'active',
        'metadata' => ['type' => 'starter'],
        'items' => ['data' => [['id' => 'si_starter', 'price' => ['id' => 'price_starter', 'product' => 'prod_starter'], 'quantity' => 1]]],
    ]]];
}

it('lets verified users and valid subscribers into the app', function (User $user): void {
    $this->actingAs($user)
        ->get(route('snap-to-track.index'))
        ->assertOk();
})->with([
    'verified user' => fn (): User => User::factory()->create(),
    'active subscription' => fn (): User => unverifiedSubscriber('active'),
    'trialing subscription' => fn (): User => unverifiedSubscriber('trialing'),
    'canceled with paid time left' => fn (): User => unverifiedSubscriber('active', ['ends_at' => now()->addWeek()]),
]);

it('sends unverified users without a valid subscription to verify their email', function (User $user): void {
    $this->actingAs($user)
        ->get(route('snap-to-track.index'))
        ->assertRedirect(route('verification.notice'));
})->with([
    'no subscription' => fn (): User => User::factory()->unverified()->create(),
    'payment incomplete' => fn (): User => unverifiedSubscriber('incomplete'),
    'payment past due' => fn (): User => unverifiedSubscriber('pastDue'),
    'subscription ended' => fn (): User => unverifiedSubscriber('canceled'),
]);

it('keeps the app locked when a Stripe customer returns from checkout before the subscription is confirmed', function (): void {
    $this->actingAs(User::factory()->unverified()->create(['stripe_id' => 'cus_waiting_for_webhook']))
        ->withSession(['checkout.started' => 'checkout-attempt'])
        ->get(route('checkout.success'))
        ->assertOk();

    $this->get(route('snap-to-track.index'))->assertRedirect(route('verification.notice'));
});

it('unlocks the app once from signed subscription webhooks without verifying the email', function (): void {
    enableBilling();
    $user = User::factory()->unverified()->create(['stripe_id' => 'cus_paid_before_verifying']);

    foreach (['customer.subscription.updated', 'customer.subscription.created', 'customer.subscription.created'] as $type) {
        sendSignedStripeWebhook(subscriptionWebhook($type))->assertSuccessful();
    }

    expect($user->subscriptions()->count())->toBe(1)
        ->and($user->refresh()->hasVerifiedEmail())->toBeFalse();

    $this->actingAs($user)->get(route('snap-to-track.index'))->assertOk();
});
