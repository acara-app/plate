<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\TierEntitlement;
use App\Enums\SubscriptionTier;
use App\Http\Controllers\Api\V2\SnapToTrack\AnalyzeSnapToTrackPhotoController;
use App\Models\AiUsage;
use App\Models\AnalysisDraft;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;

covers(AnalyzeSnapToTrackPhotoController::class);

function apiSnapPhotoBase64(): string
{
    $file = UploadedFile::fake()->image('meal.jpg', 64, 64);

    return base64_encode((string) file_get_contents((string) $file->getRealPath()));
}

function apiSnapPhotoDataUrl(): string
{
    return 'data:image/jpeg;base64,'.apiSnapPhotoBase64();
}

function fakeApiSnapAnalysis(): void
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

function stubApiSnapTier(SubscriptionTier $tier): void
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

function zeroApiSnapCreditBudget(): void
{
    config()->set('plate.tier_limits.free', [
        'rolling' => ['limit' => 0.0, 'period_hours' => 24],
        'weekly' => ['limit' => 0.0, 'period_days' => 7],
    ]);
}

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('mobile:test', ['chat:converse'])->plainTextToken;

    RateLimiter::clear('snap-to-track-analyze:'.$this->user->id);
});

it('requires authentication', function (): void {
    $this->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertUnauthorized();
});

it('analyzes a data url photo into a claimed mobile draft', function (): void {
    fakeApiSnapAnalysis();

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()]);

    $response->assertOk()
        ->assertJsonStructure(['draft_token', 'expires_at', 'analysis' => ['items', 'total_calories', 'confidence']])
        ->assertJsonPath('analysis.total_calories', 320)
        ->assertJsonPath('analysis.confidence', 74)
        ->assertJsonPath('analysis.items.0.name', 'Avocado toast');

    $draft = AnalysisDraft::query()->sole();

    expect($draft->user_id)->toBe($this->user->id)
        ->and($draft->claimed_at)->not->toBeNull()
        ->and($draft->source->value)->toBe('mobile_snap_to_track')
        ->and($draft->payload['total_calories'])->toEqual(320)
        ->and($response->json('draft_token'))->toBeString()
        ->and($draft->token_hash)->toBe(AnalysisDraft::hashToken((string) $response->json('draft_token')))
        ->and($response->json('expires_at'))->not->toBeEmpty();
});

it('accepts a bare base64 payload without a data url prefix', function (): void {
    fakeApiSnapAnalysis();

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoBase64()])
        ->assertOk();

    expect(AnalysisDraft::query()->count())->toBe(1);
});

it('validates the photo payload', function (mixed $photo): void {
    $payload = $photo === null ? [] : ['photo' => $photo];

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson(route('api.v2.snap-to-track.analyze'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['photo']);

    expect(AnalysisDraft::query()->count())->toBe(0);
})->with([
    'missing' => [null],
    'not base64' => ['@@@not-base64@@@'],
    'base64 of non-image bytes' => [fn (): string => base64_encode('this is definitely not an image')],
    'data url with no payload' => ['data:image/jpeg;base64,'],
]);

it('reports the failure and stores nothing when the analyzer throws', function (): void {
    FoodPhotoAnalyzerAgent::fake(function (): void {
        throw new Exception('AI analysis failed');
    });

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertStatus(500)
        ->assertJsonPath('error', 'analysis_failed');

    expect(AnalysisDraft::query()->count())->toBe(0);
});

it('returns 402 with the usage payload and spends nothing when the credit budget is exhausted', function (): void {
    stubApiSnapTier(SubscriptionTier::Free);
    zeroApiSnapCreditBudget();

    FoodPhotoAnalyzerAgent::fake(function (): void {
        throw new Exception('Analyzer must not run when the credit budget is exhausted');
    });

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()]);

    $response->assertStatus(402)
        ->assertJsonPath('error', 'usage_limit_exceeded')
        ->assertJsonPath('tier', 'free')
        ->assertJsonPath('limit_credits', 0)
        ->assertJsonStructure(['limit_type', 'tier_label', 'current_credits', 'resets_at', 'resets_in']);

    expect(AnalysisDraft::query()->count())->toBe(0)
        ->and(AiUsage::query()->count())->toBe(0);
});

it('returns a real 429 with Retry-After instead of the web redirect when the burst cap is hit', function (): void {
    config()->set('plate.snap_to_track.burst_caps.default', 1);

    fakeApiSnapAnalysis();

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertOk();

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()]);

    $response->assertStatus(429);

    expect($response->headers->get('Retry-After'))->not->toBeNull()
        ->and(AnalysisDraft::query()->count())->toBe(1);
});

it('keys the burst limiter per user rather than per IP', function (): void {
    config()->set('plate.snap_to_track.burst_caps.default', 1);

    fakeApiSnapAnalysis();

    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertOk();

    $this->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertStatus(429);

    $other = User::factory()->create();
    Sanctum::actingAs($other, ['chat:converse']);

    $this->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertOk();

    expect(AnalysisDraft::query()->count())->toBe(2);
});

it('localizes the analysis using the Accept-Language header over the account locale', function (): void {
    fakeApiSnapAnalysis();

    $this->user->update(['locale' => 'en']);

    $agent = new FoodPhotoAnalyzerAgent();
    app()->instance(FoodPhotoAnalyzerAgent::class, $agent);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->withHeader('Accept-Language', 'mn')
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertOk();

    expect($agent->instructions())->toContain('Монгол');
});

it('falls back to the account locale when the requested language is unsupported', function (): void {
    fakeApiSnapAnalysis();

    $this->user->update(['locale' => 'mn']);

    $agent = new FoodPhotoAnalyzerAgent();
    app()->instance(FoodPhotoAnalyzerAgent::class, $agent);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->withHeader('Accept-Language', 'de-DE,de;q=0.9')
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertOk();

    expect($agent->instructions())->toContain('Монгол');
});

it('matches a regional Accept-Language tag to its base language', function (): void {
    fakeApiSnapAnalysis();

    $this->user->update(['locale' => 'en']);

    $agent = new FoodPhotoAnalyzerAgent();
    app()->instance(FoodPhotoAnalyzerAgent::class, $agent);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->withHeader('Accept-Language', 'fr-CA,fr;q=0.9')
        ->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => apiSnapPhotoDataUrl()])
        ->assertOk();

    expect($agent->instructions())->toContain('Français');
});
