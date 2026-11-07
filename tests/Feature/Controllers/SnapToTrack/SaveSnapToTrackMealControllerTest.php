<?php

declare(strict_types=1);

use App\Actions\LogAnalyzedMeal;
use App\Http\Controllers\SnapToTrack\SaveSnapToTrackMealController;
use App\Models\AnalysisDraft;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

covers(SaveSnapToTrackMealController::class, LogAnalyzedMeal::class);

function savableDraft(array $attributes = []): array
{
    $token = Str::random(64);

    return [$token, AnalysisDraft::factory()->create(['token_hash' => AnalysisDraft::hashToken($token), ...$attributes])];
}

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('saves a guest meal at its analysis time and opens it in the logbook', function (): void {
    [$token, $draft] = savableDraft(['created_at' => now()->subMinutes(10)->startOfSecond()]);

    $response = actingAs($this->user)
        ->withSession(['snap_to_track.auth_path' => 'register'])
        ->get(route('snap-to-track.save', ['draft' => $token]));

    $meal = HealthSyncSample::query()->where('user_id', $this->user->id)->orderBy('id')->firstOrFail();

    $response->assertRedirectToRoute('health-entries.show', $meal)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('common.snap_to_track.saved')])
        ->assertInertiaFlash('analytics', [
            ['name' => 'snap_to_track_auth_completed', 'properties' => ['auth_path' => 'register']],
            ['name' => 'snap_to_track_draft_restored', 'properties' => ['draft_age_band' => 'lt_15m']],
            ['name' => 'snap_to_track_meal_logged', 'properties' => ['source' => 'public_snap_to_track', 'items_count' => 2]],
        ]);

    expect($draft->refresh()->user_id)->toBe($this->user->id)
        ->and($draft->health_group_id)->toBe($meal->group_id)
        ->and($meal->measured_at->equalTo($draft->created_at))->toBeTrue()
        ->and(collect($meal->metadata['snap_to_track']['items'])->pluck('name')->all())->toBe(['Grilled chicken breast', 'Steamed rice'])
        ->and(HealthSyncSample::query()->where('group_id', $meal->group_id)->where('type_identifier', 'dietaryEnergy')->value('value'))->toEqual(490);
});

it('opens the same meal without a duplicate when the save link is visited again', function (): void {
    [$token] = savableDraft();

    actingAs($this->user)->get(route('snap-to-track.save', ['draft' => $token]));
    $count = HealthSyncSample::query()->count();
    $meal = HealthSyncSample::query()->orderBy('id')->firstOrFail();

    actingAs($this->user)->get(route('snap-to-track.save', ['draft' => $token]))
        ->assertRedirectToRoute('health-entries.show', $meal);

    expect(HealthSyncSample::query()->count())->toBe($count);
});

it('sends the user back to Snap to Track when the meal can no longer be saved', function (Closure $attributes, string $message): void {
    [$token] = savableDraft($attributes());

    actingAs($this->user)->get(route('snap-to-track.save', ['draft' => $token]))
        ->assertRedirectToRoute('snap-to-track.index')
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => __($message)]);

    expect(HealthSyncSample::query()->count())->toBe(0);
})->with([
    'expired' => [fn (): array => ['created_at' => now()->subMinutes(90), 'expires_at' => now()->subMinutes(30)], 'common.snap_to_track.unavailable.expired'],
    'another user' => [fn (): array => ['user_id' => User::factory()->create()->id, 'claimed_at' => now()], 'common.snap_to_track.unavailable.generic'],
    'old schema' => [fn (): array => ['schema_version' => 0], 'common.snap_to_track.unavailable.generic'],
]);
