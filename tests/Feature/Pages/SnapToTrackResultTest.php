<?php

declare(strict_types=1);

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoOffer;
use App\Enums\SnapFunnelStage;
use App\Models\AnalysisDraft;
use App\Models\HealthSyncSample;
use App\Models\PlanPurchase;
use App\Models\SnapAnalyticsEvent;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Helpers\FakeGuestCheckout;

use function Pest\Laravel\mock;

function guestResultDraft(array $attributes = []): string
{
    $token = Str::random(64);

    AnalysisDraft::factory()->create([
        'token_hash' => AnalysisDraft::hashToken($token),
        ...$attributes,
    ]);

    session()->put('snap_to_track.result', ['draft' => $token, 'request_id' => 'analysis-request-1']);

    return $token;
}

function stubResultPhotoAllowance(int $used, ?PhotoOffer $offer = new PhotoOffer(productId: 7, name: 'Starter', formattedPrice: 'US$2.99')): void
{
    $allowance = mock(ManagesPhotoAnalyses::class);
    $allowance->shouldReceive('enabled')->andReturn(true);
    $allowance->shouldReceive('entitlement')->andReturn(new PhotoEntitlement(enabled: true, limit: 2, used: $used, mode: 'trial', canUpgrade: true, offer: $offer));
}

it('shows the daily overview first, then the meal estimate with its Save action', function (): void {
    guestResultDraft();

    $this->withoutVite()
        ->get(route('snap-to-track.result'))
        ->assertOk()
        ->assertSeeInOrder([
            'Daily calories',
            'a general reference, not goals calculated for you',
            'Grilled chicken breast, Steamed rice',
            '490 kcal',
            'Save this meal',
            'never dose insulin',
        ]);

    expect(AnalysisDraft::query()->sole()->user_id)->toBeNull();
});

it('explains why a meal preview is no longer available', function (array $attributes, string $explanation): void {
    guestResultDraft($attributes);

    Livewire::test('pages::snap-to-track-result')
        ->assertSee($explanation)
        ->call('save', 'register')
        ->assertNoRedirect();
})->with([
    'expired after an hour' => [['expires_at' => now()->subMinute()], 'Meal previews expire 60 minutes after analysis'],
    'already saved' => [['consumed_at' => now()], 'This meal has already been saved to a food log.'],
]);

it('sends the visitor back to the tool when there is no meal to show', function (Closure $prepare): void {
    $prepare();

    Livewire::test('pages::snap-to-track-result')
        ->assertRedirect(route('snap-to-track'));

    expect(session('snap_to_track.result'))->toBeNull();
})->with([
    'no analysis in this session' => [fn (): null => null],
    'unknown meal' => [fn () => session()->put('snap_to_track.result', ['draft' => 'missing-token', 'request_id' => null])],
    'meal claimed by another account' => [fn (): string => guestResultDraft(['user_id' => User::factory(), 'claimed_at' => now()])],
]);

it('lets a signed-in member save the meal only when they choose to', function (): void {
    $token = guestResultDraft();

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::snap-to-track-result')
        ->assertNoRedirect()
        ->call('save', 'register')
        ->assertRedirect(route('snap-to-track.save', ['draft' => $token]));

    expect(HealthSyncSample::query()->count())->toBe(0);
});

it('saves the meal once the visitor signs up or logs in', function (string $intent): void {
    $checkout = FakeGuestCheckout::install();
    stubResultPhotoAllowance(used: 1);
    $token = guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->call('save', $intent)
        ->assertRedirect(route($intent));

    expect(session('url.intended'))->toBe(route('snap-to-track.save', ['draft' => $token], absolute: false))
        ->and(session('snap_to_track.auth_path'))->toBe($intent)
        ->and(session('snap_to_track.intent'))->toBe('save')
        ->and(AnalysisDraft::query()->count())->toBe(1)
        ->and($checkout->sessions)->toBeEmpty();
})->with(['register', 'login']);

it('saves the meal shown on the page even after another analysis replaces it', function (): void {
    $shownToken = guestResultDraft();

    $component = Livewire::test('pages::snap-to-track-result');

    guestResultDraft();

    $component->call('save', 'register')
        ->assertRedirect(route('register'));

    expect(session('url.intended'))->toBe(route('snap-to-track.save', ['draft' => $shownToken], absolute: false));
});

it('shows an unknown fiber estimate as missing rather than zero', function (): void {
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->assertSeeInOrder(['Fiber', '—', '/ 28 g', 'Your meal']);
});

it('offers Free and Starter after the first scan and counts only the visible sheet offer', function (): void {
    sellPhotoPlans();
    stubResultPhotoAllowance(used: 1);
    session()->put('snap_to_track.analysis_guest_id', (string) Str::uuid());
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->assertSeeInOrder(['More scans + your own AI coach', 'US$2.99/month'])
        ->call('offerViewed', 'result')
        ->call('offerViewed', 'result_bar')
        ->call('offerViewed', 'result_bar');

    $impression = SnapAnalyticsEvent::query()->where('name', SnapFunnelStage::OfferViewed->value)->sole();
    expect($impression->properties)->toMatchArray(['placement' => 'result_bar', 'plan' => 'starter']);
    $this->assertDatabaseMissing('snap_analytics_events', ['name' => SnapFunnelStage::UpgradeClicked->value]);
});

it('keeps the first scan free to save when Starter is unavailable', function (?PhotoOffer $offer): void {
    $checkout = FakeGuestCheckout::install();
    sellPhotoPlans();
    stubResultPhotoAllowance(used: 1, offer: $offer);
    session()->put('snap_to_track.analysis_guest_id', (string) Str::uuid());
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->assertSee('Save this meal free')
        ->call('offerViewed', 'result_bar')
        ->call('upgrade', 'result_bar')
        ->assertNoRedirect()
        ->call('save', 'register')
        ->assertRedirect(route('register'));

    expect($checkout->sessions)->toBeEmpty();
    $this->assertDatabaseMissing('snap_analytics_events', ['name' => SnapFunnelStage::OfferViewed->value]);
})->with([
    'no offer for this visitor' => [null],
    'only Snap Pro offered' => [new PhotoOffer(productId: 8, name: 'Snap Pro', formattedPrice: 'US$9.00')],
]);

it('offers Starter with its price once both free scans are used, while saving stays free', function (): void {
    stubResultPhotoAllowance(used: 2);
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->assertSee('US$2.99/month')
        ->assertDontSeeHtml('href="'.route('snap-to-track').'"')
        ->call('save', 'register')
        ->assertRedirect(route('register'));
});

it('opens Stripe directly when a guest confirms Starter after a free scan', function (int $used): void {
    $checkout = FakeGuestCheckout::install();
    $starter = sellPhotoPlans();
    stubResultPhotoAllowance(used: $used, offer: new PhotoOffer(productId: $starter->id, name: $starter->name, formattedPrice: $starter->formatted_price));
    session()->put('snap_to_track.analysis_guest_id', (string) Str::uuid());
    guestResultDraft();

    $result = Livewire::test('pages::snap-to-track-result')->assertNoRedirect();
    expect($checkout->sessions)->toBeEmpty();

    $result->call('upgrade', 'result_bar')
        ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_guest_1');

    expect($checkout->sessions[0]['price'])->toBe('price_starter')
        ->and($checkout->sessions[0]['metadata'])->toMatchArray(['product_name' => 'Starter', 'type' => 'starter'])
        ->and($checkout->sessions[0]['submit_message'])->toContain('activation link')
        ->and(PlanPurchase::query()->sole()->placement)->toBe('result_bar');
})->with(['first scan' => 1, 'last free scan' => 2]);

it('lets a member log in before paying and keeps Starter selected', function (): void {
    stubResultPhotoAllowance(used: 2);
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->call('signInForPlan')
        ->assertRedirect(route('checkout.start', ['product' => 7, 'returning' => 1]));

    expect(session('snap_to_track.intent'))->toBe('upgrade');
});

it('sends a signed-in member to the plan page when they upgrade', function (int $used): void {
    $this->actingAs(User::factory()->create());
    stubResultPhotoAllowance(used: $used);
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->call('upgrade', 'result_bar')
        ->assertRedirect(route('checkout.start', ['product' => 7]));
})->with(['first scan' => 1, 'last free scan' => 2]);
