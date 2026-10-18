<?php

declare(strict_types=1);

use App\Http\Controllers\SnapToTrack\ShowSnapToTrackReviewController;
use App\Models\AnalysisDraft;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

covers(ShowSnapToTrackReviewController::class);

function reviewDraftToken(array $attributes = []): array
{
    $token = Str::random(64);

    $draft = AnalysisDraft::factory()->create([
        'token_hash' => AnalysisDraft::hashToken($token),
        ...$attributes,
    ]);

    return [$token, $draft];
}

beforeEach(function (): void {
    $this->withoutVite();

    $this->user = User::factory()->create();
});

it('restores a valid draft into the review page and claims it', function (): void {
    [$token, $draft] = reviewDraftToken();

    actingAs($this->user)
        ->get(route('snap-to-track.review', ['draft' => $token]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('snap-to-track/review')
            ->where('state', 'restored')
            ->where('draftToken', $token)
            ->where('source', 'public_snap_to_track')
            ->where('analysis.total_calories', 490)
            ->where('analysis.confidence', 82)
            ->count('analysis.items', 2));

    expect($draft->refresh()->user_id)->toBe($this->user->id);
});

it('offers a one-tap amount for added fats on the review page', function (): void {
    [$token] = reviewDraftToken(['payload' => [
        'items' => [
            ['name' => 'Mixed Salad Greens', 'calories' => 17, 'protein' => 1.2, 'carbs' => 3.3, 'fat' => 0.3, 'portion' => '2 cups', 'grams' => 100, 'match_name' => 'lettuce, mixed greens, raw', 'provenance' => 'model'],
            ['name' => 'Olive Oil Dressing', 'calories' => 119, 'protein' => 0, 'carbs' => 0, 'fat' => 13.5, 'portion' => '1 tablespoon', 'grams' => 14, 'match_name' => 'olive oil', 'provenance' => 'model'],
        ],
        'total_calories' => 136, 'total_protein' => 1.2, 'total_carbs' => 3.3, 'total_fat' => 13.8, 'confidence' => 88,
    ]]);

    actingAs($this->user)
        ->get(route('snap-to-track.review', ['draft' => $token]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->missing('addedFatOptions.0')
            ->count('addedFatOptions.1', 4)
            ->where('addedFatOptions.1.1.key', 'half_tbsp')
            ->where('addedFatOptions.1.1.calories', 59.5)
            ->where('addedFatOptions.1.2.selected', true));
});

it('flashes the draft restored event with an age band', function (): void {
    [$token, $draft] = reviewDraftToken();
    $draft->forceFill(['created_at' => now()->subMinutes(20)])->save();

    $page = actingAs($this->user)
        ->get(route('snap-to-track.review', ['draft' => $token]))
        ->inertiaPage();

    expect($page['flash']['analytics'])->toBe([
        'name' => 'snap_to_track_draft_restored',
        'properties' => ['draft_age_band' => 'lt_30m'],
    ]);
});

it('flashes auth completed before draft restored when the journey came through authentication', function (): void {
    [$token] = reviewDraftToken();

    $response = actingAs($this->user)
        ->withSession(['snap_to_track.auth_path' => 'register'])
        ->get(route('snap-to-track.review', ['draft' => $token]));

    $response->assertSessionMissing('snap_to_track.auth_path');

    expect($response->inertiaPage()['flash']['analytics'])->toBe([
        [
            'name' => 'snap_to_track_auth_completed',
            'properties' => ['auth_path' => 'register'],
        ],
        [
            'name' => 'snap_to_track_draft_restored',
            'properties' => ['draft_age_band' => 'lt_5m'],
        ],
    ]);
});

it('shows the recovery state and flashes expiry analytics for an expired draft', function (): void {
    [$token] = reviewDraftToken([
        'created_at' => now()->subMinutes(90),
        'expires_at' => now()->subMinutes(30),
    ]);

    $response = actingAs($this->user)
        ->get(route('snap-to-track.review', ['draft' => $token]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('snap-to-track/review')
            ->where('state', 'unavailable')
            ->where('reason', 'expired')
            ->where('analysis', null));

    expect($response->inertiaPage()['flash']['analytics'])->toBe([
        'name' => 'snap_to_track_draft_expired',
        'properties' => ['draft_age_band' => 'gte_60m'],
    ]);
});

it('shows the recovery state without analytics for an unknown token', function (): void {
    $response = actingAs($this->user)
        ->get(route('snap-to-track.review', ['draft' => Str::random(64)]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('state', 'unavailable')
            ->where('reason', 'invalid'));

    expect(data_get($response->inertiaPage(), 'flash.analytics'))->toBeNull();
});

it('refuses a draft claimed by another user without leaking the analysis', function (): void {
    $other = User::factory()->create();
    [$token] = reviewDraftToken(['user_id' => $other->id, 'claimed_at' => now()]);

    $response = actingAs($this->user)
        ->get(route('snap-to-track.review', ['draft' => $token]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('state', 'unavailable')
            ->where('reason', 'claimed_by_other')
            ->where('analysis', null));

    expect(data_get($response->inertiaPage(), 'flash.analytics'))->toBeNull();
});

it('shows the recovery state for a consumed draft', function (): void {
    [$token] = reviewDraftToken([
        'user_id' => $this->user->id,
        'claimed_at' => now(),
        'consumed_at' => now(),
        'health_group_id' => Str::uuid()->toString(),
    ]);

    actingAs($this->user)
        ->get(route('snap-to-track.review', ['draft' => $token]))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('state', 'unavailable')
            ->where('reason', 'consumed'));
});

it('requires authentication', function (): void {
    [$token] = reviewDraftToken();

    $this->get(route('snap-to-track.review', ['draft' => $token]))
        ->assertRedirectToRoute('login');
});
