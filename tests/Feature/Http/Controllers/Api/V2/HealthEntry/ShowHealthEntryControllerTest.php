<?php

declare(strict_types=1);

use App\Actions\GetHealthEntryAction;
use App\Http\Controllers\Api\V2\HealthEntry\ShowHealthEntryController;
use App\Models\HealthSyncSample;
use App\Models\MealPhoto;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

covers(ShowHealthEntryController::class, GetHealthEntryAction::class);

it('requires a bearer token', function (): void {
    $sample = HealthSyncSample::factory()->bloodGlucose()->create();

    $this->getJson(route('api.v2.health-entries.show', $sample))->assertUnauthorized();
});

it('shows a whole meal with its ingredients, photo, meal type and local day', function (): void {
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
    $groupId = (string) Str::uuid();
    $metadata = [
        'meal_type' => 'dinner',
        'snap_to_track' => [
            'source' => 'mobile_snap_to_track',
            'confidence' => 82,
            'items' => [['name' => 'Whole grain bread', 'portion' => '6 slices', 'calories' => 330, 'protein' => 12, 'carbs' => 62, 'fat' => 4, 'fiber' => 9, 'provenance' => 'model']],
        ],
    ];
    $sample = HealthSyncSample::factory()->carbohydrates()->create(['user_id' => $user->id, 'group_id' => $groupId, 'value' => 62, 'metadata' => $metadata, 'entry_source' => 'mobile', 'measured_at' => '2026-10-17 23:39:00']);
    HealthSyncSample::factory()->create(['user_id' => $user->id, 'group_id' => $groupId, 'type_identifier' => 'dietaryEnergy', 'value' => 330, 'unit' => 'kcal', 'metadata' => $metadata, 'entry_source' => 'mobile', 'measured_at' => '2026-10-17 23:39:00']);
    HealthSyncSample::factory()->create(['user_id' => $user->id, 'group_id' => $groupId, 'type_identifier' => 'fiber', 'value' => 9, 'unit' => 'g', 'metadata' => $metadata, 'entry_source' => 'mobile', 'measured_at' => '2026-10-17 23:39:00']);
    HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $user->id, 'group_id' => $groupId, 'value' => 120, 'measured_at' => '2026-10-17 23:39:00']);
    HealthSyncSample::factory()->create(['user_id' => $user->id, 'group_id' => $groupId, 'type_identifier' => 'stepCount', 'value' => 900, 'measured_at' => '2026-10-17 23:39:00']);
    MealPhoto::factory()->create(['user_id' => $user->id, 'health_group_id' => $groupId]);
    Sanctum::actingAs($user, ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.show', $sample))
        ->assertOk()
        ->assertJsonPath('date', '2026-10-18')
        ->assertJsonPath('glucose_unit', 'mmol/L')
        ->assertJsonPath('entry.id', $sample->id)
        ->assertJsonPath('entry.group_id', $groupId)
        ->assertJsonPath('entry.calories', 330)
        ->assertJsonPath('entry.fiber_grams', 9)
        ->assertJsonPath('entry.glucose_value', 120)
        ->assertJsonPath('entry.meal_type', 'dinner')
        ->assertJsonPath('entry.source', 'mobile')
        ->assertJsonPath('entry.measured_at', '2026-10-17T23:39:00+00:00')
        ->assertJsonPath('entry.photo_url', '/api/v2/health-entries/'.$groupId.'/photo')
        ->assertJsonPath('entry.food_analysis.confidence', 82)
        ->assertJsonPath('entry.food_analysis.items.0.portion', '6 slices');
});

it('keeps other people’s entries private', function (): void {
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create();
    Sanctum::actingAs(User::factory()->create(), ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.show', $sample))->assertForbidden();
});

it('does not open samples that are not logbook entries', function (string $type): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->create(['user_id' => $user->id, 'type_identifier' => $type, 'group_id' => (string) Str::uuid()]);
    Sanctum::actingAs($user, ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.show', $sample))->assertNotFound();
})->with(['steps' => ['stepCount'], 'fiber on its own' => ['fiber'], 'logged water' => ['water']]);

it('returns not found for an unknown entry', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.show', 999999))->assertNotFound();
});
