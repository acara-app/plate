<?php

declare(strict_types=1);

use App\Actions\Billing\ClaimPlanPurchase;
use App\Actions\Billing\StartGuestCheckout;
use App\Enums\PlanPurchaseClaim;
use App\Models\PlanPurchase;
use App\Models\User;
use App\Notifications\PlanPurchaseClaimLinkNotification;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
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

it('keeps Starter inactive when a cancellation is recorded during the claim', function (): void {
    enableBilling();
    $checkout = FakeGuestCheckout::install();
    $member = User::factory()->create();
    $purchase = PlanPurchase::factory()->paid($member->email)->create();
    $checkout->beforeSubscription = function () use ($purchase): void {
        sendSignedStripeWebhook(['id' => 'evt_cancel_during_claim', 'type' => 'customer.subscription.deleted', 'data' => ['object' => [
            'id' => $purchase->stripe_subscription_id, 'customer' => $purchase->stripe_customer_id, 'status' => 'canceled',
        ]]])->assertSuccessful();
        expect($purchase->fresh()->isCancelled())->toBeTrue();
    };

    expect(resolve(ClaimPlanPurchase::class)->handle($purchase, $member, 'email_link'))->toBe(PlanPurchaseClaim::Cancelled)
        ->and($purchase->refresh())->cancelled_at->not->toBeNull()
        ->claimed_at->toBeNull()
        ->user_id->toBeNull()
        ->and($member->refresh()->stripe_id)->toBeNull()
        ->and($member->subscriptions()->exists())->toBeFalse();
});

it('sends the slower of two claims for one account to support instead of replacing its Stripe customer', function (): void {
    $checkout = FakeGuestCheckout::install();
    $starter = sellPhotoPlans();
    $member = User::factory()->create(['email' => 'buyer@example.com']);
    $first = PlanPurchase::factory()->paid('buyer@example.com')->create(['subscription_product_id' => $starter->id]);
    $second = PlanPurchase::factory()->paid('buyer@example.com')->create(['subscription_product_id' => $starter->id]);
    $checkout->beforeSubscription = fn (): PlanPurchaseClaim => resolve(ClaimPlanPurchase::class)->handle($second, $member, 'email_link');

    expect(resolve(ClaimPlanPurchase::class)->handle($first, $member, 'email_link'))->toBe(PlanPurchaseClaim::ActivePlan)
        ->and($member->refresh()->stripe_id)->toBe($second->stripe_customer_id)
        ->and($member->subscriptions()->count())->toBe(1)
        ->and($first->refresh())
        ->claimed_at->toBeNull()
        ->claim_conflict->toBe(PlanPurchaseClaim::ActivePlan->value);
});

it('emails a lost activation link once from the sweep and reports purchases still waiting', function (): void {
    Notification::fake();
    Exceptions::fake();
    $starter = sellPhotoPlans();
    $lost = PlanPurchase::factory()->paid()->create(['subscription_product_id' => $starter->id, 'paid_at' => now()->subMinutes(10)]);
    $noEmail = PlanPurchase::factory()->paid()->create(['subscription_product_id' => $starter->id, 'email' => null, 'paid_at' => now()->subHours(2)]);

    $this->artisan('plan-purchases:claim-links')->assertSuccessful();
    $this->artisan('plan-purchases:claim-links')->assertSuccessful();
    $this->artisan('plan-purchases:claim-links', ['--report' => true])->assertSuccessful();

    Notification::assertSentOnDemandTimes(PlanPurchaseClaimLinkNotification::class, 1);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => str_contains($exception->getMessage(), $noEmail->reference())
        && ! str_contains($exception->getMessage(), $lost->reference()));
    expect($lost->refresh()->claim_link_sent_at)->not->toBeNull();
});

it('lets a buyer without a password add one once after activating', function (): void {
    FakeGuestCheckout::install();
    $starter = sellPhotoPlans();
    $purchase = PlanPurchase::factory()->paid('new-buyer@example.com')->create(['subscription_product_id' => $starter->id]);
    $this->get(route('snap-to-track.activate.open', $purchase->issueClaimToken()));

    Livewire::test('pages::snap-to-track-activate')
        ->set('acceptedDisclaimer', true)
        ->set('healthDataConsent', true)
        ->call('activate')
        ->assertNoRedirect()
        ->assertSee('Set a password')
        ->set('password', 'a sunny walk to the market')
        ->call('setPassword')
        ->assertHasNoErrors()
        ->assertSee('Password saved.')
        ->set('password', 'a different sentence entirely')
        ->call('setPassword');

    expect(Hash::check('a sunny walk to the market', (string) User::query()->sole()->password))->toBeTrue();
});

it('keeps checking for the payment when the buyer asks after the first minute', function (): void {
    $purchase = PlanPurchase::factory()->create(['checkout_returned_at' => now()]);
    session()->put('snap_to_track.purchase', $purchase->id);

    $page = Livewire::test('pages::snap-to-track-purchase')->assertSee('Check payment status');
    $purchase->update(['paid_at' => now(), 'email' => 'buyer@example.com']);

    $page->call('checkPaymentStatus')->assertSee('Check your inbox')->assertDontSee('Check payment status');
});
