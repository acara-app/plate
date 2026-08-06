<?php

declare(strict_types=1);

use App\Enums\AnalysisDraftSource;
use App\Http\Controllers\Api\V2\SnapToTrack\StoreSnapToTrackMealController;
use App\Jobs\AggregateUserDayJob;
use App\Models\AnalysisDraft;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

covers(StoreSnapToTrackMealController::class);

/**
 * @return array{0: string, 1: AnalysisDraft}
 */
function apiMobileDraftFor(User $user): array
{
    $token = Str::random(64);

    $draft = AnalysisDraft::factory()->claimed($user)->create([
        'token_hash' => AnalysisDraft::hashToken($token),
        'source' => AnalysisDraftSource::MobileSnapToTrack,
    ]);

    return [$token, $draft];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function apiReviewedMealPayload(array $overrides = []): array
{
    return [
        'items' => [
            ['name' => 'Grilled chicken', 'portion' => '100g', 'calories' => 165, 'protein' => 31, 'carbs' => 0, 'fat' => 3.6, 'provenance' => 'model'],
            ['name' => 'Steamed rice', 'calories' => 210, 'protein' => 4, 'carbs' => 45, 'fat' => 0.5, 'provenance' => 'reference'],
        ],
        'measured_at' => now()->subHour()->toIso8601String(),
        'notes' => 'Lunch bowl',
        ...$overrides,
    ];
}

beforeEach(function (): void {
    $this->user = User::factory()->create();

    Sanctum::actingAs($this->user, ['chat:converse']);
});

it('requires authentication', function (): void {
    app()->forgetInstance('auth');
    auth()->forgetGuards();

    $this->postJson(route('api.v2.snap-to-track.meal', ['draft' => Str::random(64)]), apiReviewedMealPayload())
        ->assertUnauthorized();
});

it('logs the reviewed meal as grouped mobile health samples', function (): void {
    [$token, $draft] = apiMobileDraftFor($this->user);

    $response = $this->postJson(
        route('api.v2.snap-to-track.meal', ['draft' => $token]),
        apiReviewedMealPayload(),
    );

    $response->assertCreated()
        ->assertJsonStructure(['group_id', 'measured_at', 'totals' => ['calories', 'protein', 'carbs', 'fat']]);

    expect((float) $response->json('totals.calories'))->toBe(375.0)
        ->and((float) $response->json('totals.protein'))->toBe(35.0)
        ->and((float) $response->json('totals.carbs'))->toBe(45.0)
        ->and((float) $response->json('totals.fat'))->toBe(4.1);

    $samples = HealthSyncSample::query()->where('user_id', $this->user->id)->get();

    expect($samples)->toHaveCount(4)
        ->and($samples->pluck('type_identifier')->sort()->values()->all())
        ->toBe(['carbohydrates', 'dietaryEnergy', 'protein', 'totalFat'])
        ->and($samples->firstWhere('type_identifier', 'dietaryEnergy')->value)->toBe(375.0)
        ->and($samples->pluck('entry_source')->unique()->pluck('value')->all())->toBe(['mobile'])
        ->and($samples->pluck('group_id')->unique())->toHaveCount(1)
        ->and($samples->first()->group_id)->toBe($response->json('group_id'))
        ->and($samples->first()->metadata['snap_to_track']['source'])->toBe('mobile_snap_to_track')
        ->and($samples->first()->metadata['snap_to_track']['items'])->toHaveCount(2)
        ->and($draft->refresh()->isConsumed())->toBeTrue()
        ->and($draft->health_group_id)->toBe($response->json('group_id'));
});

it('refreshes the daily aggregate for the measured day', function (): void {
    Queue::fake();

    [$token] = apiMobileDraftFor($this->user);

    $measuredAt = now()->subHour();

    $this->postJson(
        route('api.v2.snap-to-track.meal', ['draft' => $token]),
        apiReviewedMealPayload(['measured_at' => $measuredAt->toIso8601String()]),
    )->assertCreated();

    Queue::assertPushed(
        AggregateUserDayJob::class,
        fn (AggregateUserDayJob $job): bool => $job->uniqueId()
            === $this->user->id.':'.$measuredAt->copy()->utc()->toDateString(),
    );
});

it('replays a consumed draft idempotently instead of duplicating the meal', function (): void {
    [$token] = apiMobileDraftFor($this->user);

    $first = $this->postJson(route('api.v2.snap-to-track.meal', ['draft' => $token]), apiReviewedMealPayload());
    $first->assertCreated();

    $second = $this->postJson(route('api.v2.snap-to-track.meal', ['draft' => $token]), apiReviewedMealPayload());

    $second->assertOk()
        ->assertJsonPath('group_id', $first->json('group_id'));

    expect(HealthSyncSample::query()->where('user_id', $this->user->id)->count())->toBe(4);
});

it('still saves a claimed draft whose restore window has expired', function (): void {
    $token = Str::random(64);

    AnalysisDraft::factory()->claimed($this->user)->expired()->create([
        'token_hash' => AnalysisDraft::hashToken($token),
        'source' => AnalysisDraftSource::MobileSnapToTrack,
    ]);

    $this->postJson(route('api.v2.snap-to-track.meal', ['draft' => $token]), apiReviewedMealPayload())
        ->assertCreated();

    expect(HealthSyncSample::query()->where('user_id', $this->user->id)->count())->toBe(4);
});

it('returns draft_unavailable for an unknown token', function (): void {
    $this->postJson(route('api.v2.snap-to-track.meal', ['draft' => Str::random(64)]), apiReviewedMealPayload())
        ->assertNotFound()
        ->assertJsonPath('error', 'draft_unavailable')
        ->assertJsonPath('reason', 'invalid');

    expect(HealthSyncSample::query()->count())->toBe(0);
});

it('returns draft_unavailable for a draft claimed by another user', function (): void {
    [$token] = apiMobileDraftFor(User::factory()->create());

    $this->postJson(route('api.v2.snap-to-track.meal', ['draft' => $token]), apiReviewedMealPayload())
        ->assertNotFound()
        ->assertJsonPath('reason', 'invalid');

    expect(HealthSyncSample::query()->count())->toBe(0);
});

it('validates the reviewed items', function (array $overrides, string $errorKey): void {
    [$token] = apiMobileDraftFor($this->user);

    $this->postJson(route('api.v2.snap-to-track.meal', ['draft' => $token]), apiReviewedMealPayload($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$errorKey]);

    expect(HealthSyncSample::query()->count())->toBe(0);
})->with([
    'no items' => [['items' => []], 'items'],
    'missing name' => [['items' => [['calories' => 10]]], 'items.0.name'],
    'negative calories' => [['items' => [['name' => 'Toast', 'calories' => -5]]], 'items.0.calories'],
    'per-item calories over cap' => [['items' => [['name' => 'Toast', 'calories' => 5001]]], 'items.0.calories'],
    'bad provenance' => [['items' => [['name' => 'Toast', 'provenance' => 'guess']]], 'items.0.provenance'],
    'missing measured_at' => [['measured_at' => null], 'measured_at'],
    'notes too long' => [['notes' => str_repeat('a', 501)], 'notes'],
]);

it('rejects a meal whose combined macros exceed the caps', function (): void {
    [$token] = apiMobileDraftFor($this->user);

    $items = array_fill(0, 3, ['name' => 'Cake', 'calories' => 2000, 'protein' => 10, 'carbs' => 100, 'fat' => 20]);

    $this->postJson(
        route('api.v2.snap-to-track.meal', ['draft' => $token]),
        apiReviewedMealPayload(['items' => $items]),
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items']);

    expect(HealthSyncSample::query()->count())->toBe(0);
});
