<?php

declare(strict_types=1);

use App\Http\Controllers\HealthEntry\ShowHealthEntryController;
use App\Models\HealthSyncSample;
use App\Models\MealPhoto;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

covers(ShowHealthEntryController::class);

it('shows a whole meal with its ingredients, photo, meal type and local day', function (): void {
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
    $groupId = (string) Str::uuid();
    $metadata = [
        'meal_type' => 'dinner',
        'snap_to_track' => [
            'source' => 'web',
            'confidence' => 82,
            'items' => [['name' => 'Whole grain bread', 'portion' => '6 slices', 'calories' => 330, 'protein' => 12, 'carbs' => 62, 'fat' => 4, 'provenance' => 'model']],
        ],
    ];
    $sample = HealthSyncSample::factory()->carbohydrates()->fromWeb()->create(['user_id' => $user->id, 'group_id' => $groupId, 'value' => 62, 'metadata' => $metadata, 'measured_at' => '2026-10-17 23:39:00']);
    HealthSyncSample::factory()->fromWeb()->create(['user_id' => $user->id, 'group_id' => $groupId, 'type_identifier' => 'dietaryEnergy', 'value' => 330, 'unit' => 'kcal', 'metadata' => $metadata, 'measured_at' => '2026-10-17 23:39:00']);
    HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $user->id, 'group_id' => $groupId, 'value' => 120, 'measured_at' => '2026-10-17 23:39:00']);
    MealPhoto::factory()->create(['user_id' => $user->id, 'health_group_id' => $groupId]);

    $this->actingAs($user)
        ->get(route('health-entries.show', $sample))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('health-entries/show')
            ->where('date', '2026-10-18')
            ->where('entry.id', $sample->id)
            ->where('entry.calories', 330)
            ->where('entry.glucose_value', 120)
            ->where('entry.meal_type', 'dinner')
            ->where('entry.photo_url', route('health-entries.photo', ['group' => $groupId], absolute: false))
            ->where('entry.food_analysis.items.0.portion', '6 slices')
            ->has('glucoseReadingTypes'));
});

it('keeps other people’s entries private', function (): void {
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('health-entries.show', $sample))
        ->assertForbidden();
});

it('does not open samples that are not logbook entries', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->create(['user_id' => $user->id, 'type_identifier' => 'stepCount']);

    $this->actingAs($user)
        ->get(route('health-entries.show', $sample))
        ->assertNotFound();
});

it('sends guests to log in', function (): void {
    $sample = HealthSyncSample::factory()->bloodGlucose()->create();

    $this->get(route('health-entries.show', $sample))->assertRedirectToRoute('login');
});
