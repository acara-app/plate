<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\TierEntitlement;
use App\Enums\SubscriptionTier;
use App\Http\Controllers\SnapToTrack\AnalyzeSnapToTrackPhotoController;
use App\Models\AiUsage;
use App\Models\AnalysisDraft;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\actingAs;

covers(AnalyzeSnapToTrackPhotoController::class);

function stubTierForSnapToTrack(SubscriptionTier $tier): void
{
    app()->instance(ResolvesUserTier::class, new class($tier) implements ResolvesUserTier
    {
        public function __construct(private readonly SubscriptionTier $tier) {}

        public function resolve(User $user): TierEntitlement
        {
            return new TierEntitlement(tier: $this->tier);
        }
    });
}

function zeroFreeCreditBudget(): void
{
    config()->set('plate.tier_limits.free', [
        'rolling' => ['limit' => 0.0, 'period_hours' => 24],
        'weekly' => ['limit' => 0.0, 'period_days' => 7],
    ]);
}

function fakeAuthenticatedAnalysis(): void
{
    FoodPhotoAnalyzerAgent::fake([[
        'items' => [
            ['name' => 'Avocado toast', 'calories' => 320, 'protein' => 9, 'carbs' => 30, 'fat' => 19, 'portion' => '1 slice'],
        ],
        'total_calories' => 320,
        'total_protein' => 9,
        'total_carbs' => 30,
        'total_fat' => 19,
        'confidence' => 74,
    ]]);
}

beforeEach(function (): void {
    $this->user = User::factory()->create();

    RateLimiter::clear('snap-to-track-analyze:'.$this->user->id);
});

it('analyzes an uploaded photo into a claimed draft and redirects to review', function (): void {
    fakeAuthenticatedAnalysis();

    $response = actingAs($this->user)->post(route('snap-to-track.analyze'), [
        'photo' => UploadedFile::fake()->image('meal.jpg'),
    ]);

    $draft = AnalysisDraft::query()->sole();

    expect($draft->user_id)->toBe($this->user->id)
        ->and($draft->claimed_at)->not->toBeNull()
        ->and($draft->source->value)->toBe('authenticated_snap_to_track')
        ->and($draft->payload['total_calories'])->toEqual(320);

    $response->assertRedirectContains('/app/snap-to-track/review/')
        ->assertInertiaFlash('analytics', [
            'name' => 'snap_to_track_result_viewed',
            'properties' => [
                'source' => 'authenticated_snap_to_track',
                'items_count' => 1,
                'confidence_band' => 'medium',
            ],
        ]);
});

it('returns a photo error and stores nothing when the analysis fails', function (): void {
    FoodPhotoAnalyzerAgent::fake(function (): void {
        throw new Exception('AI analysis failed');
    });

    actingAs($this->user)
        ->from(route('snap-to-track.index'))
        ->post(route('snap-to-track.analyze'), [
            'photo' => UploadedFile::fake()->image('meal.jpg'),
        ])
        ->assertRedirect(route('snap-to-track.index'))
        ->assertSessionHasErrors('photo');

    expect(AnalysisDraft::query()->count())->toBe(0);
});

it('validates the uploaded photo', function (array $payload, string $errorKey): void {
    actingAs($this->user)
        ->post(route('snap-to-track.analyze'), $payload)
        ->assertSessionHasErrors($errorKey);
})->with([
    'missing photo' => [[], 'photo'],
    'not an image' => [fn (): array => ['photo' => UploadedFile::fake()->create('meal.pdf', 100, 'application/pdf')], 'photo'],
    'oversized image' => [fn (): array => ['photo' => UploadedFile::fake()->image('meal.jpg')->size(11000)], 'photo'],
]);

it('throttles repeated analyses per user with a friendly retry message', function (): void {
    fakeAuthenticatedAnalysis();

    foreach (range(1, 5) as $attempt) {
        actingAs($this->user)->post(route('snap-to-track.analyze'), [
            'photo' => UploadedFile::fake()->image("meal-{$attempt}.jpg"),
        ])->assertRedirect();
    }

    actingAs($this->user)
        ->from(route('snap-to-track.index'))
        ->post(route('snap-to-track.analyze'), [
            'photo' => UploadedFile::fake()->image('meal-6.jpg'),
        ])
        ->assertRedirect(route('snap-to-track.index'))
        ->assertSessionHasErrors([
            'photo' => "You've hit this hour's scan limit. You can scan again in about 60 minutes.",
        ]);
});

it('leaves other throttled routes returning a plain 429', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 6) as $attempt) {
        actingAs($user)->post(route('disclaimer.accept'), ['accepted' => '1']);
    }

    actingAs($user)
        ->post(route('disclaimer.accept'), ['accepted' => '1'])
        ->assertStatus(429)
        ->assertSessionDoesntHaveErrors('photo');
});

it('blocks the scan before spending when the credit budget is exhausted', function (): void {
    stubTierForSnapToTrack(SubscriptionTier::Free);
    zeroFreeCreditBudget();

    FoodPhotoAnalyzerAgent::fake(function (): void {
        throw new Exception('Analyzer must not run when the credit budget is exhausted');
    });

    $response = actingAs($this->user)->post(route('snap-to-track.analyze'), [
        'photo' => UploadedFile::fake()->image('meal.jpg'),
    ]);

    $response->assertRedirect(route('snap-to-track.index'))
        ->assertSessionHas('snap_to_track_credit_limit')
        ->assertInertiaFlash('analytics', [
            'name' => 'snap_to_track_limit_reached',
            'properties' => [
                'source' => 'authenticated_snap_to_track',
                'gate' => 'credits',
                'tier' => 'free',
            ],
        ]);

    $payload = session('snap_to_track_credit_limit');

    expect($payload['tier'])->toBe('free')
        ->and($payload['limit_credits'])->toBe(0)
        ->and($payload['resets_at'])->not->toBeEmpty()
        ->and(AnalysisDraft::query()->count())->toBe(0)
        ->and(AiUsage::query()->count())->toBe(0);
});

it('scans past an exhausted budget when premium enforcement is inactive', function (): void {
    zeroFreeCreditBudget();
    fakeAuthenticatedAnalysis();

    actingAs($this->user)->post(route('snap-to-track.analyze'), [
        'photo' => UploadedFile::fake()->image('meal.jpg'),
    ])->assertRedirectContains('/app/snap-to-track/review/');

    expect(AnalysisDraft::query()->count())->toBe(1);
});

it('applies tier-aware burst caps to scanning', function (): void {
    config()->set('plate.snap_to_track.burst_caps.free', 1);
    config()->set('plate.snap_to_track.burst_caps.basic', 2);

    fakeAuthenticatedAnalysis();
    stubTierForSnapToTrack(SubscriptionTier::Free);

    actingAs($this->user)->post(route('snap-to-track.analyze'), [
        'photo' => UploadedFile::fake()->image('meal-1.jpg'),
    ])->assertRedirectContains('/app/snap-to-track/review/');

    actingAs($this->user)
        ->from(route('snap-to-track.index'))
        ->post(route('snap-to-track.analyze'), [
            'photo' => UploadedFile::fake()->image('meal-2.jpg'),
        ])
        ->assertRedirect(route('snap-to-track.index'))
        ->assertSessionHasErrors('photo');

    $supporter = User::factory()->create();
    stubTierForSnapToTrack(SubscriptionTier::Basic);

    foreach (range(1, 2) as $attempt) {
        actingAs($supporter)->post(route('snap-to-track.analyze'), [
            'photo' => UploadedFile::fake()->image("supporter-{$attempt}.jpg"),
        ])->assertRedirectContains('/app/snap-to-track/review/');
    }

    actingAs($supporter)
        ->from(route('snap-to-track.index'))
        ->post(route('snap-to-track.analyze'), [
            'photo' => UploadedFile::fake()->image('supporter-3.jpg'),
        ])
        ->assertSessionHasErrors('photo');
});

it('keeps the default burst cap for unrestricted entitlements', function (): void {
    config()->set('plate.snap_to_track.burst_caps.default', 1);
    config()->set('plate.snap_to_track.burst_caps.free', 5);

    fakeAuthenticatedAnalysis();

    actingAs($this->user)->post(route('snap-to-track.analyze'), [
        'photo' => UploadedFile::fake()->image('meal-1.jpg'),
    ])->assertRedirectContains('/app/snap-to-track/review/');

    actingAs($this->user)
        ->from(route('snap-to-track.index'))
        ->post(route('snap-to-track.analyze'), [
            'photo' => UploadedFile::fake()->image('meal-2.jpg'),
        ])
        ->assertSessionHasErrors('photo');
});
