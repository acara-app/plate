<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Contracts\Services\StripeServiceContract;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoModel;
use App\Data\Billing\PhotoOffer;
use App\Data\FoodAnalysisData;
use App\Enums\SnapFunnelStage;
use App\Jobs\ProcessPhotoAnalysis;
use App\Models\AnalysisDraft;
use App\Models\PhotoAnalysisRequest;
use App\Models\SnapJourney;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Mockery\MockInterface;
use RyanChandler\LaravelCloudflareTurnstile\Contracts\ClientInterface;
use RyanChandler\LaravelCloudflareTurnstile\Facades\Turnstile;

beforeEach(function (): void {
    RateLimiter::clear('snap-to-track:127.0.0.1');
    RateLimiter::clear('snap-to-track-upload:127.0.0.1');
    Turnstile::fake();
    FoodPhotoAnalyzerAgent::fake([[
        'items' => [['name' => 'Grilled chicken', 'calories' => 280, 'protein' => 52, 'carbs' => 0, 'fat' => 6, 'portion' => '1 breast']],
        'total_calories' => 280, 'total_protein' => 52, 'total_carbs' => 0, 'total_fat' => 6, 'confidence' => 82,
    ]]);
    Queue::fake([ProcessPhotoAnalysis::class]);
});

function offerStarterInSnapJourney(SubscriptionProduct $starter, int $used): void
{
    app()->instance(ManagesPhotoAnalyses::class, new readonly class($starter, $used) implements ManagesPhotoAnalyses
    {
        public function __construct(private SubscriptionProduct $starter, private int $used) {}

        public function enabled(): bool
        {
            return true;
        }

        public function entitlement(?User $user, ?string $guestId): PhotoEntitlement
        {
            return new PhotoEntitlement(true, 2, $this->used, null, 'trial', true, new PhotoOffer($this->starter->id, $this->starter->name, $this->starter->formatted_price));
        }

        public function analyze(PhotoAnalysisContext $context, Closure $analyze): FoodAnalysisData
        {
            return $analyze(PhotoModel::standard());
        }
    });
}

function fakeSnapJourneyCheckout(): ArrayObject
{
    $metadata = new ArrayObject;

    test()->mock(StripeServiceContract::class, function (MockInterface $stripe) use ($metadata): void {
        $stripe->shouldReceive('hasActiveSubscription')->andReturnFalse();
        $stripe->shouldReceive('hasIncompletePayment')->andReturnFalse();
        $stripe->shouldReceive('getPriceIdFromLookupKey')->andReturn('price_starter');
        $stripe->shouldReceive('createSubscriptionCheckout')->andReturnUsing(function (User $user, string $type, string $price, string $success, string $cancel, array $checkoutMetadata) use ($metadata): string {
            $metadata->exchangeArray($checkoutMetadata);

            return 'https://checkout.stripe.com/c/pay/cs_test_snap';
        });
    });

    return $metadata;
}

function analyzeSnapJourneyPhoto(): Testable
{
    return Livewire::test('pages::snap-to-track')
        ->set('turnstileToken', ClientInterface::RESPONSE_DUMMY_TOKEN)
        ->set('photo', UploadedFile::fake()->image('meal.jpg'))
        ->call('analyze');
}

function snapFunnelReport(array $options = []): array
{
    Artisan::call('snap:funnel-report', ['--json' => true, ...$options]);

    return json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
}

function snapFunnelSteps(array $report, string $path): array
{
    return array_column($report['paths'][$path], 'journeys');
}

it('follows a guest from a selected photo to a payment confirmed after they leave Stripe without returning', function (): void {
    $starter = sellPhotoPlans();
    $guest = (string) Str::uuid();
    session()->put('snap_to_track.analysis_guest_id', $guest);
    $this->withCookie('photo_guest', $guest)->withoutVite();

    $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile/15E148')
        ->get(route('snap-to-track', ['utm_source' => 'Newsletter']))
        ->assertOk();
    $scanner = analyzeSnapJourneyPhoto();
    app()->call([new ProcessPhotoAnalysis(PhotoAnalysisRequest::query()->sole()->id), 'handle']);
    $scanner->call('refreshAnalysis')->assertRedirect(route('snap-to-track.result'));

    offerStarterInSnapJourney($starter, used: 1);
    Livewire::test('pages::snap-to-track-result');
    Livewire::test('pages::snap-to-track-result')->call('upgrade', 'result')->assertRedirect(route('checkout.start', $starter));

    $this->post(route('register.store'), ['name' => 'Snap Guest', 'email' => 'snap-guest@example.com', 'password' => 'password1234', 'accepted_disclaimer' => '1']);
    Auth::user()->markEmailAsVerified();
    $metadata = fakeSnapJourneyCheckout();
    $this->post(route('checkout.subscription.store'), ['product_id' => $starter->id, 'billing_interval' => 'monthly'])
        ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_snap');

    Auth::logout();
    $this->flushSession();
    $buyer = User::query()->sole();
    $buyer->update(['stripe_id' => 'cus_snap']);
    DB::table('subscriptions')->insert([
        'user_id' => $buyer->id, 'type' => 'starter', 'stripe_id' => 'sub_snap', 'stripe_status' => 'active',
        'stripe_price' => 'price_starter', 'quantity' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $paidInvoice = ['id' => 'evt_first_payment', 'type' => 'invoice.paid', 'livemode' => true, 'data' => ['object' => [
        'customer' => 'cus_snap', 'status' => 'paid', 'amount_paid' => 299, 'currency' => 'usd', 'billing_reason' => 'subscription_create',
        'parent' => ['subscription_details' => ['subscription' => 'sub_snap', 'metadata' => $metadata->getArrayCopy()]],
    ]]];
    sendSignedStripeWebhook($paidInvoice)->assertSuccessful();
    sendSignedStripeWebhook($paidInvoice)->assertSuccessful();

    $report = snapFunnelReport();

    expect(SnapJourney::query()->sole())
        ->device->toBe('mobile')
        ->acquisition_source->toBe('newsletter')
        ->and(snapFunnelSteps($report, 'scan'))->toBe([1, 1, 1, 1])
        ->and(snapFunnelSteps($report, 'upgrade'))->toBe([1, 1, 1, 1, 1, 1])
        ->and($report['raw_events'])->toMatchArray([
            SnapFunnelStage::ResultViewed->value => 1,
            SnapFunnelStage::FirstPayment->value => 1,
        ])
        ->and($report['payments'])->toMatchArray(['live' => 1, 'attributed' => 1, 'subscriptions' => 1, 'subscriptions_paid' => 1]);
});

it('shows an abandoned checkout from the limit wall without inventing a payment', function (): void {
    $starter = sellPhotoPlans();
    $guest = (string) Str::uuid();
    User::factory()->withoutTwoFactor()->create(['email' => 'returning@example.com']);
    session()->put('snap_to_track.analysis_guest_id', $guest);
    offerStarterInSnapJourney($starter, used: 2);

    Livewire::test('pages::snap-to-track')->call('upgrade', 'wall');
    $this->withCookie('photo_guest', $guest)->withoutVite()
        ->post(route('login.store'), ['email' => 'returning@example.com', 'password' => 'password']);
    fakeSnapJourneyCheckout();
    $this->post(route('checkout.subscription.store'), ['product_id' => $starter->id, 'billing_interval' => 'monthly']);
    $this->get(route('checkout.cancel'))->assertOk();

    $report = snapFunnelReport(['--by' => 'placement']);

    expect(snapFunnelSteps($report, 'upgrade'))->toBe([1, 1, 1, 1, 1, 0])
        ->and(array_keys($report['segments']))->toBe(['wall'])
        ->and(array_column($report['problems'], 'journeys', 'label'))->toMatchArray([
            'Checkout cancelled' => 1,
            'Checkout opened, never returned or paid' => 0,
        ]);
});

it('reports an interrupted analysis as an error and its retry as the same journey', function (): void {
    sellPhotoPlans();
    session()->put('snap_to_track.analysis_guest_id', (string) Str::uuid());

    $scanner = analyzeSnapJourneyPhoto();
    new ProcessPhotoAnalysis(PhotoAnalysisRequest::query()->sole()->id)->failed(new TimeoutExceededException);
    $scanner->call('refreshAnalysis')->call('retryAnalysis');
    app()->call([new ProcessPhotoAnalysis(PhotoAnalysisRequest::query()->latest('id')->firstOrFail()->id), 'handle']);

    $report = snapFunnelReport();

    expect($report['journeys'])->toBe(1)
        ->and($report['raw_events'][SnapFunnelStage::AnalysisRequested->value])->toBe(2)
        ->and(array_column($report['problems'], 'journeys', 'label'))->toMatchArray([
            'Analysis failed · timeout' => 1,
            'Analysis requested, no outcome recorded' => 0,
        ]);
});

it('records the free save path from the guest result', function (): void {
    $starter = sellPhotoPlans();
    $draft = Str::random(64);
    AnalysisDraft::factory()->create(['token_hash' => AnalysisDraft::hashToken($draft)]);
    session()->put('snap_to_track.analysis_guest_id', (string) Str::uuid());
    session()->put('snap_to_track.result', ['draft' => $draft, 'request_id' => (string) Str::uuid()]);
    offerStarterInSnapJourney($starter, used: 1);

    Livewire::test('pages::snap-to-track-result')->call('save', 'register')->assertRedirect(route('register'));

    expect(snapFunnelSteps(snapFunnelReport(), 'save'))->toBe([1, 1, 0, 0]);
});

it('leaves staff journeys out of the funnel unless asked', function (): void {
    sellPhotoPlans();
    config(['sponsors.admin_emails' => ['staff@example.com']]);
    $guest = (string) Str::uuid();
    session()->put('snap_to_track.analysis_guest_id', $guest);
    SnapJourney::factory()->create(['guest_hash' => hash('sha256', $guest)]);
    User::factory()->withoutTwoFactor()->create(['email' => 'staff@example.com']);

    $this->withCookie('photo_guest', $guest)->post(route('login.store'), ['email' => 'staff@example.com', 'password' => 'password']);

    expect(snapFunnelReport()['journeys'])->toBe(0)
        ->and(snapFunnelReport(['--include-internal' => true])['journeys'])->toBe(1);
});

it('prints the funnel and the stage definitions', function (): void {
    $this->artisan('snap:funnel-report', ['--by' => 'device'])
        ->expectsOutputToContain('Upgrade path (from offer viewed)')
        ->expectsOutputToContain('Raw event (repeats included, not unique journeys)')
        ->expectsOutputToContain('2026-10-24: Umami events changed meaning')
        ->assertSuccessful();

    $this->artisan('snap:funnel-report', ['--definitions' => true])
        ->expectsOutputToContain(SnapFunnelStage::FirstPayment->value)
        ->assertSuccessful();
});
