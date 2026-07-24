<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Http\Controllers\SnapToTrack\AnalyzeSnapToTrackPhotoController;
use App\Models\AnalysisDraft;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;

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
    config()->set('plate.snap_to_track.activation_funnel', true);

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

it('throttles repeated analyses per user', function (): void {
    fakeAuthenticatedAnalysis();

    foreach (range(1, 5) as $attempt) {
        actingAs($this->user)->post(route('snap-to-track.analyze'), [
            'photo' => UploadedFile::fake()->image("meal-{$attempt}.jpg"),
        ])->assertRedirect();
    }

    actingAs($this->user)->post(route('snap-to-track.analyze'), [
        'photo' => UploadedFile::fake()->image('meal-6.jpg'),
    ])->assertStatus(429);
});

it('returns not found when the activation funnel is disabled', function (): void {
    config()->set('plate.snap_to_track.activation_funnel', false);

    actingAs($this->user)
        ->post(route('snap-to-track.analyze'), [
            'photo' => UploadedFile::fake()->image('meal.jpg'),
        ])
        ->assertNotFound();
});
