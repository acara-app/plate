<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Http\Controllers\SnapToTrack\AnalyzeSnapToTrackPhotoController;
use App\Models\AiUsage;
use App\Models\AnalysisDraft;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;

covers(AnalyzeSnapToTrackPhotoController::class);

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
});

it('analyzes an uploaded photo, logs the meal and opens it', function (): void {
    fakeAuthenticatedAnalysis();

    $response = actingAs($this->user)->post(route('snap-to-track.analyze'), [
        'photo' => UploadedFile::fake()->image('meal.jpg'),
    ]);

    $draft = AnalysisDraft::query()->sole();
    $meal = HealthSyncSample::query()->where('user_id', $this->user->id)->orderBy('id')->first();

    expect($draft->source->value)->toBe('authenticated_snap_to_track')
        ->and($draft->isConsumed())->toBeTrue()
        ->and($meal->group_id)->toBe($draft->health_group_id)
        ->and($meal->metadata['snap_to_track']['items'][0]['name'])->toBe('Avocado toast');

    $response->assertRedirectToRoute('health-entries.show', $meal)
        ->assertInertiaFlash('analytics', [
            [
                'name' => 'snap_to_track_result_viewed',
                'properties' => [
                    'source' => 'authenticated_snap_to_track',
                    'items_count' => 1,
                    'confidence_band' => 'medium',
                ],
            ],
            [
                'name' => 'snap_to_track_meal_logged',
                'properties' => ['source' => 'authenticated_snap_to_track', 'items_count' => 1],
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

it('blocks the scan and stores nothing when the AI allowance is used up', function (): void {
    enableBilling();
    AiUsage::factory()->create(['user_id' => $this->user->id, 'cost' => 0.50]);
    FoodPhotoAnalyzerAgent::fake(function (): void {
        throw new Exception('Analyzer must not run when the AI allowance is used up');
    });

    actingAs($this->user)->post(route('snap-to-track.analyze'), [
        'photo' => UploadedFile::fake()->image('meal.jpg'),
    ])->assertRedirect(route('snap-to-track.index'))
        ->assertSessionHasErrors('photo')
        ->assertInertiaFlash('analytics', [
            'name' => 'snap_to_track_limit_reached',
            'properties' => ['source' => 'authenticated_snap_to_track', 'gate' => 'credits', 'tier' => 'free'],
        ]);

    expect(AnalysisDraft::query()->count())->toBe(0);
});
