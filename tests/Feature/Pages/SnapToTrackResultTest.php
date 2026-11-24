<?php

declare(strict_types=1);

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoModel;
use App\Data\Billing\PhotoOffer;
use App\Data\FoodAnalysisData;
use App\Models\AnalysisDraft;
use App\Models\HealthSyncSample;
use App\Models\PlanPurchase;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Helpers\FakeGuestCheckout;

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

function stubResultPhotoAllowance(PhotoEntitlement $entitlement): void
{
    app()->instance(ManagesPhotoAnalyses::class, new readonly class($entitlement) implements ManagesPhotoAnalyses
    {
        public function __construct(private PhotoEntitlement $entitlement) {}

        public function enabled(): bool
        {
            return true;
        }

        public function entitlement(?User $user, ?string $guestId): PhotoEntitlement
        {
            return $this->entitlement;
        }

        public function analyze(PhotoAnalysisContext $context, Closure $analyze): FoodAnalysisData
        {
            return $analyze(PhotoModel::standard());
        }
    });
}

function trialResultAllowance(int $used, ?PhotoOffer $offer = new PhotoOffer(productId: 7, name: 'Starter', formattedPrice: 'US$2.99')): PhotoEntitlement
{
    return new PhotoEntitlement(enabled: true, limit: 2, used: $used, mode: 'trial', canUpgrade: true, offer: $offer);
}

it('shows the daily overview first, then the meal estimate with its Save action', function (): void {
    guestResultDraft();

    $this->withoutVite()
        ->get(route('snap-to-track.result'))
        ->assertOk()
        ->assertSeeInOrder([
            'Daily calories',
            'a general reference, not goals calculated for you',
            'Your meal',
            'Grilled chicken breast, Steamed rice',
            '490 kcal',
            'AI estimate',
            'Save this meal',
            'Saving is free',
            'Already have an account? Log in',
            '1 breast (170g)',
            'never dose insulin',
            'Analyze another photo',
        ]);

    expect(AnalysisDraft::query()->sole()->user_id)->toBeNull();
});

it('explains why a meal preview is no longer available', function (array $attributes, string $explanation): void {
    guestResultDraft($attributes);

    Livewire::test('pages::snap-to-track-result')
        ->assertNoRedirect()
        ->assertSee('This meal preview is no longer available')
        ->assertSee($explanation)
        ->assertSee('Analyze another photo')
        ->assertDontSee('Save this meal');
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
        ->assertSee('Save this meal')
        ->assertDontSee('Already have an account? Log in')
        ->call('save', 'register')
        ->assertRedirect(route('snap-to-track.save', ['draft' => $token]));

    expect(HealthSyncSample::query()->count())->toBe(0);
});

it('saves the meal once the visitor signs up or logs in', function (string $intent): void {
    $token = guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->call('save', $intent)
        ->assertRedirect(route($intent));

    expect(session('url.intended'))->toBe(route('snap-to-track.save', ['draft' => $token], absolute: false))
        ->and(session('snap_to_track.auth_path'))->toBe($intent)
        ->and(session('snap_to_track.intent'))->toBe('save')
        ->and(AnalysisDraft::query()->count())->toBe(1);
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

it('keeps the first scan about the meal and its remaining free scan', function (): void {
    stubResultPhotoAllowance(trialResultAllowance(used: 1));
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->assertSeeInOrder(['Save this meal', '1 free scan remaining', 'Analyze another photo', 'Save this meal free'])
        ->assertDontSee('Continue with Starter');
});

it('offers Starter with its price once both free scans are used, while saving stays free', function (): void {
    stubResultPhotoAllowance(trialResultAllowance(used: 2, offer: new PhotoOffer(productId: 7, name: 'Starter', formattedPrice: 'US$2.99')));
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->assertSee('This meal is still free to save.')
        ->assertSeeInOrder([
            'Save this meal',
            "Continue with Starter\u{00A0}· US\$2.99/month",
            'A fresh AI allowance every day.',
            'Continue with Google',
            'Not now — save this meal free',
            'Save this meal free',
        ])
        ->assertDontSee('Analyze another photo');
});

it('opens Stripe for a guest who chooses Starter', function (): void {
    $checkout = FakeGuestCheckout::install();
    $starter = sellPhotoPlans();
    stubResultPhotoAllowance(trialResultAllowance(used: 2, offer: new PhotoOffer(productId: $starter->id, name: 'Starter', formattedPrice: 'US$2.99')));
    session()->put('snap_to_track.analysis_guest_id', (string) Str::uuid());
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->call('upgrade', 'result_bar')
        ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_guest_1');

    expect($checkout->sessions[0]['price'])->toBe('price_starter')
        ->and($checkout->sessions[0]['metadata'])->toMatchArray(['product_name' => 'Starter', 'type' => 'starter'])
        ->and(PlanPurchase::query()->sole()->placement)->toBe('result_bar');
});

it('lets a member sign in before paying and keeps Starter selected', function (string $via, array $parameters): void {
    stubResultPhotoAllowance(trialResultAllowance(used: 2));
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->call('signInForPlan', $via)
        ->assertRedirect(route('checkout.start', ['product' => 7, ...$parameters]));

    expect(session('snap_to_track.intent'))->toBe('upgrade');
})->with([
    'google' => ['google', ['via' => 'google']],
    'email' => ['login', ['returning' => 1]],
]);

it('sends a signed-in member to the plan page when they upgrade', function (): void {
    $this->actingAs(User::factory()->create());
    stubResultPhotoAllowance(trialResultAllowance(used: 2));
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->call('upgrade')
        ->assertRedirect(route('checkout.start', ['product' => 7]));
});
