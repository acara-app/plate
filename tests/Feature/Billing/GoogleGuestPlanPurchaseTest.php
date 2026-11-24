<?php

declare(strict_types=1);

use App\Enums\ConsentPurpose;
use App\Enums\PlanPurchaseClaim;
use App\Enums\SnapFunnelStage;
use App\Models\PlanPurchase;
use App\Models\SnapAnalyticsEvent;
use App\Models\SnapJourney;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;
use Tests\Helpers\FakeGuestCheckout;

beforeEach(function (): void {
    $this->withoutVite();
    FakeGuestCheckout::install();
    $guest = (string) Str::uuid();
    $this->purchase = PlanPurchase::factory()->paid('buyer@gmail.com')->create([
        'subscription_product_id' => sellPhotoPlans()->id,
        'snap_journey_id' => SnapJourney::factory(),
        'guest_hash' => hash('sha256', $guest),
        'checkout_returned_at' => now(),
    ]);
    $this->withCookie('photo_guest', $guest)->withSession(['snap_to_track.purchase' => $this->purchase->id]);
});

function startPurchaseGoogleOAuth(string $email = 'buyer@gmail.com', bool $verified = true, ?string $hostedDomain = null, ?Exception $failure = null): void
{
    $user = new SocialiteUser();
    $user->setRaw(['email_verified' => $verified, 'hd' => $hostedDomain]);
    $user->id = 'google-buyer';
    $user->email = $email;
    $user->name = 'Starter Buyer';

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('with')->with(['prompt' => 'select_account'])->andReturnSelf();
    $provider->shouldReceive('redirect')->once()->andReturn(new RedirectResponse('https://accounts.google.com'));
    if ($failure instanceof Exception) {
        $provider->shouldReceive('user')->once()->andThrow($failure);
    } else {
        $provider->shouldReceive('user')->andReturn($user);
    }

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    test()->get(route('snap-to-track.purchase.google'))->assertRedirect('https://accounts.google.com');
}

function expectGooglePurchaseClaimed(PlanPurchase $purchase, User $buyer): void
{
    $purchase->refresh();
    $buyer->refresh();

    test()->assertAuthenticatedAs($buyer);
    expect($purchase->isClaimed())->toBeTrue()
        ->and($purchase->user_id)->toBe($buyer->id)
        ->and($purchase->claim_method)->toBe('google')
        ->and($buyer->google_id)->toBe('google-buyer')
        ->and($buyer->hasVerifiedEmail())->toBeTrue()
        ->and($buyer->stripe_id)->toBe($purchase->stripe_customer_id)
        ->and($buyer->subscriptions()->sole()->stripe_id)->toBe($purchase->stripe_subscription_id)
        ->and($buyer->hasActiveSubscription())->toBeTrue();

    $event = SnapAnalyticsEvent::query()->where('name', SnapFunnelStage::PurchaseClaimed->value)->sole();
    expect($event->journey_id)->toBe($purchase->snap_journey_id)
        ->and($event->properties['method'])->toBe('google');
}

function expectGooglePurchaseUnclaimed(PlanPurchase $purchase): void
{
    $purchase->refresh();
    expect($purchase->claimed_at)->toBeNull()
        ->and($purchase->user_id)->toBeNull()
        ->and($purchase->claim_method)->toBeNull()
        ->and(Subscription::query()->where('stripe_id', $purchase->stripe_subscription_id)->exists())->toBeFalse()
        ->and(SnapAnalyticsEvent::query()->where('name', SnapFunnelStage::PurchaseClaimed->value)->exists())->toBeFalse();
}

it('requires both agreements before activating Starter for a new Google buyer', function (): void {
    $this->get(route('snap-to-track.purchase'))->assertSee('Continue with Google')->assertSee('Resend the link');
    startPurchaseGoogleOAuth('Buyer@Gmail.com');
    $this->get(route('auth.google.callback'))->assertRedirectToRoute('snap-to-track.activate', ['method' => 'google']);
    $this->get(route('snap-to-track.purchase'))->assertOk()->assertSee('Resend the link');

    $form = Livewire::withQueryParams(['method' => 'google'])->test('pages::snap-to-track-activate')
        ->call('activate')->assertHasErrors(['acceptedDisclaimer', 'healthDataConsent'])
        ->set('acceptedDisclaimer', true)->call('activate')->assertHasErrors(['healthDataConsent']);

    expect(User::query()->exists())->toBeFalse();
    expectGooglePurchaseUnclaimed($this->purchase);

    $form->set('healthDataConsent', true)->call('activate')->assertRedirect(route('snap-to-track.index'));
    $buyer = User::query()->sole();
    expect($buyer->email)->toBe('buyer@gmail.com')
        ->and($buyer->terms_accepted_at)->not->toBeNull()
        ->and($buyer->privacy_accepted_at)->not->toBeNull()
        ->and($buyer->consentDecision(ConsentPurpose::ProcessHealthData))->toBeTrue();
    expectGooglePurchaseClaimed($this->purchase, $buyer);
});

it('activates the session-bound purchase for the existing Google buyer', function (string $email, ?string $hostedDomain): void {
    $this->purchase->update(['email' => $email]);
    $member = User::factory()->create(['email' => $email]);
    $other = PlanPurchase::factory()->paid($email)->create();

    startPurchaseGoogleOAuth($email, hostedDomain: $hostedDomain);
    $this->get(route('auth.google.callback', ['purchase' => $other->id]))->assertRedirectToRoute('snap-to-track.activate', ['method' => 'google']);
    $this->get(route('snap-to-track.activate', ['method' => 'google']))->assertRedirectToRoute('snap-to-track.index');

    expectGooglePurchaseClaimed($this->purchase, $member);
    expect($other->refresh()->claimed_at)->toBeNull()
        ->and(User::query()->count())->toBe(1);
})->with([
    'Gmail' => ['buyer@gmail.com', null],
    'Workspace' => ['buyer@workspace.example', 'workspace.example'],
]);

it('switches to the Google buyer without carrying over another signed-in account session', function (): void {
    $otherMember = User::factory()->create();
    $buyer = User::factory()->create(['email' => 'buyer@gmail.com']);
    $this->actingAs($otherMember)->withSession([
        'auth.password_confirmed_at' => now()->timestamp,
        'login.id' => $otherMember->id,
        'snap_to_track.result.draft' => 'previous-user-meal',
    ]);

    startPurchaseGoogleOAuth();
    $this->get(route('auth.google.callback'))->assertRedirectToRoute('snap-to-track.activate', ['method' => 'google']);
    $this->get(route('snap-to-track.activate', ['method' => 'google']))->assertRedirectToRoute('snap-to-track.index')
        ->assertSessionMissing('auth.password_confirmed_at')->assertSessionMissing('login.id')
        ->assertSessionMissing('snap_to_track.result.draft')->assertSessionHas('snap_to_track.purchase', $this->purchase->id);

    expectGooglePurchaseClaimed($this->purchase, $buyer);
    expect($otherMember->subscriptions()->exists())->toBeFalse();
});

it('keeps the original email link usable when Google cannot prove ownership', function (string $checkoutEmail, string $googleEmail, bool $verified, ?string $hostedDomain, ?string $linkedId, string $reason, bool $localVerified = true): void {
    $this->purchase->update(['email' => $checkoutEmail]);
    $member = User::factory()->create([
        'email' => $checkoutEmail,
        'google_id' => $linkedId,
        'email_verified_at' => $localVerified ? now() : null,
    ]);
    $token = $this->purchase->issueClaimToken();
    startPurchaseGoogleOAuth($googleEmail, $verified, $hostedDomain);

    $this->followingRedirects()->get(route('auth.google.callback'))
        ->assertOk()->assertSee('Resend the link')->assertSeeHtml(route('snap-to-track.purchase.google'))->assertSee($reason);

    $this->assertGuest();
    expectGooglePurchaseUnclaimed($this->purchase);
    expect($member->refresh()->google_id)->toBe($linkedId)
        ->and($member->hasVerifiedEmail())->toBe($localVerified)
        ->and($member->subscriptions()->exists())->toBeFalse()
        ->and($this->purchase->claim_token_hash)->toBe(PlanPurchase::hashToken($token));

    $this->followingRedirects()->get(route('snap-to-track.activate.open', $token))->assertSee('Log in to add Starter');
})->with([
    'a different address' => ['buyer@gmail.com', 'different@gmail.com', true, null, null, "doesn't match the email used at checkout"],
    'an unverified address' => ['buyer@gmail.com', 'buyer@gmail.com', false, null, null, 'Google could not verify your email'],
    'a third-party address' => ['buyer@example.com', 'buyer@example.com', true, null, null, 'Google does not manage this email domain'],
    'an unrelated hosted domain' => ['buyer@example.com', 'buyer@example.com', true, 'other.example', null, 'Google does not manage this email domain'],
    'an unverified local account' => ['buyer@gmail.com', 'buyer@gmail.com', true, null, null, 'existing Acara Plate account has not been verified', false],
    'a different linked Google account' => ['buyer@gmail.com', 'buyer@gmail.com', true, null, 'original-google-id', 'Google identity linked to this account does not match'],
]);

it('returns to the same purchase when Google sign-in does not finish', function (array $callbackQuery, ?Exception $failure): void {
    startPurchaseGoogleOAuth(failure: $failure);

    $this->get(route('auth.google.callback', $callbackQuery))->assertRedirectToRoute('snap-to-track.purchase');
    $this->get(route('snap-to-track.purchase'))->assertSee('Resend the link')->assertSeeHtml(route('snap-to-track.purchase.google'))
        ->assertSee('Google sign-in did not finish')->assertSee($this->purchase->reference());

    $this->assertGuest();
    expectGooglePurchaseUnclaimed($this->purchase);
    expect(User::query()->exists())->toBeFalse();
})->with([
    'buyer cancels' => [['error' => 'access_denied'], null],
    'provider rejects callback' => [[], new InvalidStateException()],
]);

it('completes a pending Google claim when polling sees the payment webhook', function (): void {
    Notification::fake();
    $buyer = User::factory()->create(['email' => 'buyer@gmail.com']);
    startPurchaseGoogleOAuth();
    $this->purchase->update(['paid_at' => null, 'stripe_customer_id' => null, 'stripe_subscription_id' => null, 'email' => null]);

    $this->get(route('auth.google.callback'))->assertRedirectToRoute('snap-to-track.activate', ['method' => 'google']);
    $this->get(route('snap-to-track.activate', ['method' => 'google']))->assertRedirectToRoute('snap-to-track.purchase');
    $page = Livewire::test('pages::snap-to-track-purchase')->assertSee('Confirming your payment');
    expectGooglePurchaseUnclaimed($this->purchase);

    sendSignedStripeWebhook(paidGuestPurchaseWebhook(['plan_purchase' => $this->purchase->id], 'buyer@gmail.com'))->assertSuccessful();
    $page->call('pollPaymentStatus')->assertRedirect(route('snap-to-track.activate', ['method' => 'google']));
    $this->get(route('snap-to-track.activate', ['method' => 'google']))->assertRedirectToRoute('snap-to-track.index');

    expectGooglePurchaseClaimed($this->purchase, $buyer);
});

it('keeps the Google purchase claimable and shows support for an existing billing conflict', function (PlanPurchaseClaim $conflict, string $reason): void {
    $buyer = User::factory()->create(['email' => 'buyer@gmail.com', 'stripe_id' => 'cus_existing']);
    $original = $conflict === PlanPurchaseClaim::ActivePlan
        ? Subscription::factory()->active()->create(['user_id' => $buyer->id])
        : null;

    startPurchaseGoogleOAuth();
    $this->followingRedirects()->get(route('auth.google.callback'))
        ->assertSee($reason)->assertSeeHtml('Purchase%20reference%3A%20'.$this->purchase->reference());

    expectGooglePurchaseUnclaimed($this->purchase);
    expect($this->purchase->claim_conflict)->toBe($conflict->value)
        ->and($buyer->refresh()->stripe_id)->toBe('cus_existing')
        ->and($buyer->subscriptions()->pluck('id')->all())->toBe($original === null ? [] : [$original->id]);
})->with([
    'another active plan' => [PlanPurchaseClaim::ActivePlan, 'already has an active plan'],
    'another Stripe customer' => [PlanPurchaseClaim::OtherCustomer, 'already has billing details'],
]);

it('cannot start a Google claim for a purchase from a different browser', function (): void {
    $this->flushSession();

    Socialite::shouldReceive('driver')->never();
    $this->get(route('snap-to-track.purchase.google', ['purchase' => $this->purchase->id]))->assertRedirectToRoute('snap-to-track.purchase');
    $this->get(route('snap-to-track.purchase'))->assertDontSee('Continue with Google');
    expectGooglePurchaseUnclaimed($this->purchase);
});

it('offers Google only for an eligible purchase', function (string $changedColumn): void {
    $this->purchase->update([$changedColumn => $changedColumn === 'paid_at' ? null : now()]);
    Socialite::shouldReceive('driver')->never();

    $this->get(route('snap-to-track.purchase'))->assertDontSee('Continue with Google');
    $this->get(route('snap-to-track.purchase.google'))->assertRedirectToRoute('snap-to-track.purchase');
})->with(['not paid yet' => 'paid_at', 'claimed' => 'claimed_at', 'cancelled' => 'cancelled_at']);

it('requires the same browser proof when a previously opened consent form is submitted', function (): void {
    startPurchaseGoogleOAuth();
    $this->get(route('auth.google.callback'))->assertRedirectToRoute('snap-to-track.activate', ['method' => 'google']);
    $form = Livewire::withQueryParams(['method' => 'google'])->test('pages::snap-to-track-activate')
        ->set('acceptedDisclaimer', true)->set('healthDataConsent', true);

    $this->flushSession();
    $form->call('activate');

    $this->assertGuest();
    expect(User::query()->exists())->toBeFalse();
    expectGooglePurchaseUnclaimed($this->purchase);
});
