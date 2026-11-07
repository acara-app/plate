<?php

declare(strict_types=1);

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoModel;
use App\Data\Billing\PhotoOffer;
use App\Data\FoodAnalysisData;
use App\Models\AnalysisDraft;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

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

it('sends a signed-in member to the editable review', function (): void {
    $token = guestResultDraft();

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::snap-to-track-result')
        ->assertRedirect(route('snap-to-track.review', ['draft' => $token]));
});

it('keeps the meal waiting in review while the visitor signs up or logs in', function (string $intent): void {
    $token = guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->call('save', $intent)
        ->assertRedirect(route($intent));

    expect(session('url.intended'))->toBe(route('snap-to-track.review', ['draft' => $token], absolute: false))
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

    expect(session('url.intended'))->toBe(route('snap-to-track.review', ['draft' => $shownToken], absolute: false));
});

it('shows an unknown fiber estimate as missing rather than zero', function (): void {
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->assertSeeInOrder(['Fiber', '—', '/28 g', 'Your meal']);
});

it('offers the plan and its price after the meal and its Save action', function (): void {
    stubResultPhotoAllowance(trialResultAllowance(used: 1, offer: new PhotoOffer(
        productId: 7,
        name: 'Starter',
        formattedPrice: 'US$2.99',
    )));
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->assertSeeInOrder([
            'Save this meal',
            'Keep tracking with Starter — US$2.99/month',
            'Renews monthly until cancelled. Cancel through Billing settings.',
            'Not now — save this meal free',
            'Or keep tracking with Starter · US$2.99/month',
        ]);
});

it('keeps saving free once the free scans are used up', function (): void {
    stubResultPhotoAllowance(trialResultAllowance(used: 2));
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->assertSee('This meal is still free to save.')
        ->assertSee('Keep tracking with Starter — US$2.99/month')
        ->assertDontSee('Analyze another photo');
});

it('sends the visitor to checkout when they upgrade', function (?PhotoOffer $offer, string $destination, array $parameters): void {
    stubResultPhotoAllowance(trialResultAllowance(used: 2, offer: $offer));
    guestResultDraft();

    Livewire::test('pages::snap-to-track-result')
        ->call('upgrade')
        ->assertRedirect(route($destination, $parameters));

    expect(session('snap_to_track.intent'))->toBe('upgrade')
        ->and(session('url.intended'))->toBe(route('checkout.subscription', absolute: false));
})->with([
    'offered plan' => [new PhotoOffer(productId: 7, name: 'Starter', formattedPrice: '$2.99'), 'checkout.start', ['product' => 7]],
    'no plan offered' => [null, 'checkout.subscription', []],
]);
