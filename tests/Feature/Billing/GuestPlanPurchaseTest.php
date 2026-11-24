<?php

declare(strict_types=1);

use App\Actions\Billing\StartGuestCheckout;
use App\Models\PlanPurchase;
use App\Models\User;
use App\Notifications\PlanPurchaseClaimLinkNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Livewire\Livewire;
use Tests\Helpers\FakeGuestCheckout;

it('never opens a second checkout while one is open or waiting for Stripe', function (): void {
    $checkout = FakeGuestCheckout::install();
    sellPhotoPlans();
    $guest = (string) Str::uuid();

    $first = resolve(StartGuestCheckout::class)->handle($guest, 'result');
    $second = resolve(StartGuestCheckout::class)->handle($guest, 'wall');

    $this->withCookie('photo_guest', $guest)->withoutVite()
        ->get(route('snap-to-track.purchase', ['checkout' => 'success']))
        ->assertSee('Confirming your payment');

    expect($second)->toBe($first)
        ->and(resolve(StartGuestCheckout::class)->handle($guest, 'result'))->toBe(route('snap-to-track.purchase'))
        ->and($checkout->sessions)->toHaveCount(1)
        ->and(PlanPurchase::query()->count())->toBe(1);
});

it('adds Starter to an existing account after the buyer logs in and shows the same success again', function (): void {
    FakeGuestCheckout::install();
    $starter = sellPhotoPlans();
    $member = User::factory()->withoutTwoFactor()->create(['email' => 'buyer@example.com']);
    $purchase = PlanPurchase::factory()->paid('buyer@example.com')->create(['subscription_product_id' => $starter->id]);
    $link = route('snap-to-track.activate.open', $purchase->issueClaimToken());

    $this->get($link)->assertRedirect(route('snap-to-track.activate'));
    Livewire::test('pages::snap-to-track-activate')
        ->assertSee('Log in to add Starter')
        ->call('logIn')
        ->assertRedirect(route('login'));
    $this->post(route('login.store'), ['email' => 'buyer@example.com', 'password' => 'password'])
        ->assertRedirect(route('snap-to-track.activate', absolute: false));
    Livewire::test('pages::snap-to-track-activate')->call('activate')->assertRedirect(route('snap-to-track.index'));

    $this->get($link)->assertRedirect(route('snap-to-track.activate'));
    Livewire::test('pages::snap-to-track-activate')->assertSee("You're all set", false);

    expect($member->refresh()->stripe_id)->toBe($purchase->stripe_customer_id)
        ->and($member->hasActiveSubscription())->toBeTrue()
        ->and($purchase->refresh()->claim_method)->toBe('email_link');
});

it('sends the buyer to support and keeps the purchase claimable when the account already has billing', function (Closure $prepare, string $explanation): void {
    FakeGuestCheckout::install();
    $starter = sellPhotoPlans();
    $member = User::factory()->create(['email' => 'buyer@example.com', 'stripe_id' => 'cus_member']);
    $prepare($member);
    $purchase = PlanPurchase::factory()->paid('buyer@example.com')->create(['subscription_product_id' => $starter->id]);
    $this->get(route('snap-to-track.activate.open', $purchase->issueClaimToken()));

    $this->actingAs($member);
    Livewire::test('pages::snap-to-track-activate')
        ->call('activate')
        ->assertSee("Let's sort this out together", false)
        ->assertSee($explanation);

    expect($purchase->refresh())
        ->claimed_at->toBeNull()
        ->paid_at->not->toBeNull()
        ->claim_token_hash->not->toBeNull()
        ->and($member->refresh()->stripe_id)->toBe('cus_member');
})->with([
    'another active plan' => [fn (User $member): Subscription => Subscription::factory()->active()->create(['user_id' => $member->id]), 'already has an active plan'],
    'another Stripe customer' => [fn (User $member): null => null, 'already has billing details'],
]);

it('sends a fresh link when the old one has expired and retires the old one', function (): void {
    Notification::fake();
    $starter = sellPhotoPlans();
    $purchase = PlanPurchase::factory()->paid('buyer@example.com')->create(['subscription_product_id' => $starter->id]);
    $token = $purchase->issueClaimToken();
    $this->travel(PlanPurchase::CLAIM_LINK_MINUTES + 1)->minutes();

    $this->get(route('snap-to-track.activate.open', $token));
    Livewire::test('pages::snap-to-track-activate')
        ->assertSee('This link has expired')
        ->call('resendLink')
        ->assertSee('Check your inbox');

    $this->get(route('snap-to-track.activate.open', $token));
    Livewire::test('pages::snap-to-track-activate')->assertSee('This link is no longer valid');

    Notification::assertSentOnDemandTimes(PlanPurchaseClaimLinkNotification::class, 1);
});

it('shows a purchase that Stripe cancelled before activation instead of activating it', function (): void {
    $starter = sellPhotoPlans();
    $purchase = PlanPurchase::factory()->paid()->create(['subscription_product_id' => $starter->id]);
    $token = $purchase->issueClaimToken();

    sendSignedStripeWebhook(['id' => 'evt_guest_cancelled', 'type' => 'customer.subscription.deleted', 'data' => ['object' => [
        'id' => $purchase->stripe_subscription_id, 'customer' => $purchase->stripe_customer_id, 'status' => 'canceled',
    ]]])->assertSuccessful();

    $this->get(route('snap-to-track.activate.open', $token));
    Livewire::test('pages::snap-to-track-activate')->assertSee('This purchase was cancelled');
});
