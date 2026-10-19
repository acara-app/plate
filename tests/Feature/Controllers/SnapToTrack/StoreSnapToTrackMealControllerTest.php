<?php

declare(strict_types=1);

use App\Http\Controllers\SnapToTrack\StoreSnapToTrackMealController;
use App\Models\AnalysisDraft;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

covers(StoreSnapToTrackMealController::class);

function storableDraftFor(User $user): array
{
    $token = Str::random(64);

    $draft = AnalysisDraft::factory()->claimed($user)->create([
        'token_hash' => AnalysisDraft::hashToken($token),
    ]);

    return [$token, $draft];
}

function reviewedMealPayload(array $overrides = []): array
{
    return [
        'items' => [
            ['name' => 'Grilled chicken', 'portion' => '100g', 'grams' => 100, 'calories' => 165, 'protein' => 31, 'carbs' => 0, 'fat' => 3.6, 'provenance' => 'model'],
            ['name' => 'Steamed rice', 'calories' => 210, 'protein' => 4, 'carbs' => 45, 'fat' => 0.5, 'provenance' => 'reference'],
        ],
        'measured_at' => '2026-10-16T11:57:00Z',
        'notes' => 'Lunch bowl',
        ...$overrides,
    ];
}

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('saves the reviewed meal and opens its logbook day in the user timezone', function (string $timezone, string $date): void {
    [$token, $draft] = storableDraftFor($this->user);
    $this->user->update(['timezone' => $timezone]);

    $response = actingAs($this->user)
        ->post(route('snap-to-track.review.store', ['draft' => $token]), reviewedMealPayload(['measured_at' => '2026-10-17T23:30:00Z']));

    $response->assertRedirectToRoute('health-entries.index', ['date' => $date])
        ->assertSessionMissing('snap_to_track_saved_group')
        ->assertSessionMissing('snap_to_track_saved_date')
        ->assertInertiaFlash('analytics', [
            'name' => 'snap_to_track_meal_logged',
            'properties' => [
                'source' => 'public_snap_to_track',
                'items_count' => 2,
            ],
        ]);

    $samples = HealthSyncSample::query()->where('user_id', $this->user->id)->get();

    expect($samples)->toHaveCount(4)
        ->and($samples->firstWhere('type_identifier', 'dietaryEnergy')->value)->toBe(375.0)
        ->and($samples->first()->metadata['snap_to_track']['items'])->toHaveCount(2)
        ->and($samples->first()->metadata['snap_to_track']['items'][0]['grams'])->toEqual(100)
        ->and($samples->first()->metadata['snap_to_track']['items'][1]['grams'])->toBeNull()
        ->and($draft->refresh()->isConsumed())->toBeTrue();
})->with([
    'next day in Ulaanbaatar' => ['Asia/Ulaanbaatar', '2026-10-18'],
    'same day in Los Angeles' => ['America/Los_Angeles', '2026-10-17'],
    'same day in UTC' => ['UTC', '2026-10-17'],
]);

it('does not create a duplicate entry when the save is retried', function (): void {
    [$token] = storableDraftFor($this->user);

    actingAs($this->user)->post(route('snap-to-track.review.store', ['draft' => $token]), reviewedMealPayload());
    actingAs($this->user)
        ->post(route('snap-to-track.review.store', ['draft' => $token]), reviewedMealPayload())
        ->assertRedirectToRoute('health-entries.index', ['date' => '2026-10-16']);

    expect(HealthSyncSample::query()->where('user_id', $this->user->id)->count())->toBe(4);
});

it('still saves a claimed draft whose restore window has expired', function (): void {
    $token = Str::random(64);
    AnalysisDraft::factory()->claimed($this->user)->expired()->create([
        'token_hash' => AnalysisDraft::hashToken($token),
    ]);

    actingAs($this->user)
        ->post(route('snap-to-track.review.store', ['draft' => $token]), reviewedMealPayload())
        ->assertRedirectToRoute('health-entries.index', ['date' => '2026-10-16']);

    expect(HealthSyncSample::query()->where('user_id', $this->user->id)->count())->toBe(4);
});

it('redirects to the recovery state for a draft owned by another user', function (): void {
    $other = User::factory()->create();
    [$token] = storableDraftFor($other);

    actingAs($this->user)
        ->post(route('snap-to-track.review.store', ['draft' => $token]), reviewedMealPayload())
        ->assertRedirect(route('snap-to-track.review', ['draft' => $token]));

    expect(HealthSyncSample::query()->count())->toBe(0);
});

it('rejects adversarial payloads', function (array $overrides, string $errorKey): void {
    [$token] = storableDraftFor($this->user);

    actingAs($this->user)
        ->from(route('snap-to-track.review', ['draft' => $token]))
        ->post(route('snap-to-track.review.store', ['draft' => $token]), reviewedMealPayload($overrides))
        ->assertRedirect(route('snap-to-track.review', ['draft' => $token]))
        ->assertSessionHasErrors($errorKey);

    expect(HealthSyncSample::query()->count())->toBe(0);
})->with([
    'negative calories' => [['items' => [['name' => 'Soup', 'calories' => -10]]], 'items.0.calories'],
    'excessive item calories' => [['items' => [['name' => 'Feast', 'calories' => 9000]]], 'items.0.calories'],
    'excessive combined calories' => [['items' => [['name' => 'A', 'calories' => 3000], ['name' => 'B', 'calories' => 3000]]], 'items'],
    'empty item list' => [['items' => []], 'items'],
    'too many items' => [['items' => array_fill(0, 31, ['name' => 'Bite', 'calories' => 10])], 'items'],
    'missing item name' => [['items' => [['calories' => 100]]], 'items.0.name'],
    'unknown provenance' => [['items' => [['name' => 'Soup', 'provenance' => 'hacked']]], 'items.0.provenance'],
    'negative weight' => [['items' => [['name' => 'Soup', 'grams' => -1]]], 'items.0.grams'],
    'excessive weight' => [['items' => [['name' => 'Soup', 'grams' => 2001]]], 'items.0.grams'],
    'missing timestamp' => [['measured_at' => null], 'measured_at'],
    'overlong notes' => [['notes' => str_repeat('a', 501)], 'notes'],
]);

it('treats missing macros as zero instead of failing', function (): void {
    [$token] = storableDraftFor($this->user);

    actingAs($this->user)
        ->post(route('snap-to-track.review.store', ['draft' => $token]), reviewedMealPayload([
            'items' => [['name' => 'Mystery dish', 'calories' => 300]],
        ]))
        ->assertRedirectToRoute('health-entries.index', ['date' => '2026-10-16']);

    $samples = HealthSyncSample::query()->where('user_id', $this->user->id)->get();

    expect($samples->firstWhere('type_identifier', 'carbohydrates')->value)->toBe(0.0)
        ->and($samples->firstWhere('type_identifier', 'dietaryEnergy')->value)->toBe(300.0);
});

it('stores raw item names without interpreting markup', function (): void {
    [$token] = storableDraftFor($this->user);

    actingAs($this->user)
        ->post(route('snap-to-track.review.store', ['draft' => $token]), reviewedMealPayload([
            'items' => [['name' => '<script>alert(1)</script>', 'calories' => 100]],
            'notes' => null,
        ]))
        ->assertRedirectToRoute('health-entries.index', ['date' => '2026-10-16']);

    $sample = HealthSyncSample::query()->where('user_id', $this->user->id)->first();

    expect($sample->metadata['snap_to_track']['items'][0]['name'])->toBe('<script>alert(1)</script>')
        ->and($sample->notes)->toBe('<script>alert(1)</script>');
});
