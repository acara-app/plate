<?php

declare(strict_types=1);

use App\Enums\GlucoseUnit;
use App\Enums\GoalChoice;
use App\Enums\MealPhotoRoute;
use App\Enums\Sex;
use App\Http\Controllers\Api\V2\HealthEntry\ListHealthEntryController;
use App\Models\HealthSyncSample;
use App\Models\MealPhoto;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

covers(ListHealthEntryController::class, MealPhotoRoute::class);

it('requires a bearer token', function (): void {
    $this->getJson(route('api.v2.health-entries.index'))->assertUnauthorized();
});

it('sends the synced exercise total alongside the per-minute entries the iOS logbook still lists', function (): void {
    $user = User::factory()->create(['timezone' => 'UTC']);
    $sample = HealthSyncSample::factory()->exercise()->fromMobileSync()->create([
        'user_id' => $user->id, 'value' => 1, 'measured_at' => '2026-10-21 09:00:00',
    ]);
    Sanctum::actingAs($user, ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.index', ['date' => '2026-10-21']))
        ->assertJsonPath('wellness.synced_exercise_minutes', 1)
        ->assertJsonCount(1, 'entries')
        ->assertJsonPath('entries.0.id', $sample->id)
        ->assertJsonPath('entries.0.exercise_duration_minutes', 1);
});

it('returns today’s logbook in the user’s timezone', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 17:00:00', 'UTC'));
    Sanctum::actingAs(User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']), ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.index'))
        ->assertOk()
        ->assertExactJson([
            'date' => '2026-10-19',
            'today' => '2026-10-19',
            'timezone' => 'Asia/Ulaanbaatar',
            'week_start' => '2026-10-19',
            'new_entry_at' => null,
            'glucose_unit' => 'mmol/L',
            'entries' => [],
            'logged_dates' => [],
            'goals' => null,
            'wellness' => [
                'steps' => 0,
                'step_goal' => 10000,
                'sleep_hours' => null,
                'sleep_synced' => false,
                'sleep_quality' => null,
                'stress_level' => null,
                'water_ml' => 0,
                'water_synced_ml' => 0,
                'water_goal_ml' => 2000,
                'synced_exercise_minutes' => null,
            ],
        ]);
});

it('lists only the chosen local day’s entries for the signed-in user', function (string $date, array $values): void {
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
    $otherUser = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);

    foreach (['2026-10-17 15:30:00' => 101, '2026-10-17 23:30:00' => 102, '2026-10-18 15:59:00' => 103, '2026-10-18 16:00:00' => 104] as $measuredAt => $value) {
        HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $user->id, 'value' => $value, 'measured_at' => $measuredAt]);
    }

    HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $otherUser->id, 'measured_at' => '2026-10-18 01:00:00']);
    Sanctum::actingAs($user, ['chat:converse']);

    $response = $this->getJson(route('api.v2.health-entries.index', ['date' => $date]))
        ->assertOk()
        ->assertJsonPath('date', $date);

    expect(collect($response->json('entries'))->pluck('glucose_value')->all())->toBe(array_map(GlucoseUnit::mgDlToMmolL(...), $values));
})->with([
    'late local evening' => ['2026-10-17', [101]],
    'local morning after a UTC late evening' => ['2026-10-18', [103, 102]],
    'next local day starts at 16:00 UTC' => ['2026-10-19', [104]],
]);

it('marks the days of the selected Monday-to-Sunday week that have entries', function (): void {
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);

    foreach (['2026-10-11 15:00:00', '2026-10-12 23:00:00', '2026-10-13 02:00:00', '2026-10-18 15:00:00', '2026-10-18 16:30:00'] as $measuredAt) {
        HealthSyncSample::factory()->weight()->fromWeb()->create(['user_id' => $user->id, 'measured_at' => $measuredAt]);
    }

    Sanctum::actingAs($user, ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.index', ['date' => '2026-10-15']))
        ->assertOk()
        ->assertJsonPath('week_start', '2026-10-12')
        ->assertJsonPath('logged_dates', ['2026-10-13', '2026-10-18']);
});

it('shares daily nutrition goals once the profile allows them', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['age' => 30, 'date_of_birth' => null, 'height' => 180, 'weight' => 80, 'sex' => Sex::Male, 'goal_choice' => GoalChoice::HealthyEating, 'derived_activity_multiplier' => 1.5]);
    Sanctum::actingAs($user, ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.index'))
        ->assertOk()
        ->assertJsonPath('goals', ['calories' => 2370, 'protein' => 119, 'carbs' => 296, 'fat' => 79, 'fiber' => 33]);
});

it('suggests the current account-local time on a past day for new entries', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 17:00:00', 'UTC'));
    Sanctum::actingAs(User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']), ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.index', ['date' => '2026-10-17']))
        ->assertOk()
        ->assertJsonPath('new_entry_at', '2026-10-17T01:00:00+08:00');
});

it('reports the glucose unit the user reads values in', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['units_preference' => GlucoseUnit::MgDl]);
    Sanctum::actingAs($user, ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.index'))
        ->assertOk()
        ->assertJsonPath('glucose_unit', 'mg/dL');
});

it('links meal photos through the bearer-authenticated photo endpoint', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 12:00:00', 'UTC'));
    $user = User::factory()->create();
    $groupId = (string) Str::uuid();
    $metadata = ['meal_type' => 'lunch', 'snap_to_track' => ['source' => 'mobile_snap_to_track', 'confidence' => 80, 'items' => [['name' => 'Rice', 'portion' => '150 g', 'calories' => 200, 'protein' => 4, 'carbs' => 44, 'fat' => 0.5, 'provenance' => 'model']]]];
    HealthSyncSample::factory()->carbohydrates()->create(['user_id' => $user->id, 'group_id' => $groupId, 'value' => 44, 'metadata' => $metadata, 'entry_source' => 'mobile', 'measured_at' => '2026-10-18 11:00:00']);
    HealthSyncSample::factory()->create(['user_id' => $user->id, 'group_id' => $groupId, 'type_identifier' => 'dietaryEnergy', 'value' => 200, 'unit' => 'kcal', 'metadata' => $metadata, 'entry_source' => 'mobile', 'measured_at' => '2026-10-18 11:00:00']);
    MealPhoto::factory()->create(['user_id' => $user->id, 'health_group_id' => $groupId]);
    Sanctum::actingAs($user, ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.index'))
        ->assertOk()
        ->assertJsonPath('entries.0.photo_url', '/api/v2/health-entries/'.$groupId.'/photo')
        ->assertJsonPath('entries.0.source', 'mobile')
        ->assertJsonPath('entries.0.meal_type', 'lunch')
        ->assertJsonPath('entries.0.calories', 200)
        ->assertJsonPath('entries.0.food_analysis.items.0.grams', 150)
        ->assertJsonPath('logged_dates', ['2026-10-18']);
});

it('uses UTC days for an account without a timezone', function (): void {
    $user = User::factory()->create(['timezone' => null]);
    HealthSyncSample::factory()->weight()->fromWeb()->create(['user_id' => $user->id, 'measured_at' => '2026-10-17 23:30:00']);
    Sanctum::actingAs($user, ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.index', ['date' => '2026-10-17']))
        ->assertOk()
        ->assertJsonPath('timezone', 'UTC')
        ->assertJsonCount(1, 'entries');
});

it('rejects malformed logbook dates', function (mixed $date): void {
    Sanctum::actingAs(User::factory()->create(), ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.index', ['date' => $date]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('date');
})->with(['impossible month' => ['2026-13-01'], 'day first' => ['18-10-2026'], 'array' => [['2026-10-18']]]);
