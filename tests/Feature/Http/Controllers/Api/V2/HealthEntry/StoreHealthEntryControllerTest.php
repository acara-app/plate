<?php

declare(strict_types=1);

use App\Enums\GlucoseUnit;
use App\Http\Controllers\Api\V2\HealthEntry\StoreHealthEntryController;
use App\Http\Requests\HealthEntryRequest;
use App\Jobs\AggregateUserDayJob;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

covers(StoreHealthEntryController::class, HealthEntryRequest::class);

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 17:00:00', 'UTC'));
    $this->user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
});

it('requires a bearer token', function (): void {
    $this->postJson(route('api.v2.health-entries.store'), ['log_type' => 'insulin'])->assertUnauthorized();
});

it('logs every kind of entry from the app', function (array $payload, string $type, float $value, string $field, mixed $expected): void {
    Sanctum::actingAs($this->user, ['chat:converse']);

    $response = $this->postJson(route('api.v2.health-entries.store'), [...$payload, 'measured_at' => '2026-10-18T16:30:00Z'])
        ->assertCreated()
        ->assertJsonPath('date', '2026-10-19')
        ->assertJsonPath('glucose_unit', 'mmol/L')
        ->assertJsonPath('entry.source', 'mobile')
        ->assertJsonPath('entry.measured_at', '2026-10-18T16:30:00+00:00')
        ->assertJsonPath('entry.'.$field, $expected);

    $sample = HealthSyncSample::query()->where('user_id', $this->user->id)->where('type_identifier', $type)->sole();

    expect($sample->value)->toBe($value)
        ->and($response->json('entry.id'))->toBeInt()
        ->and(HealthSyncSample::query()->where('user_id', $this->user->id)->pluck('entry_source')->map->value->unique()->all())->toBe(['mobile']);
})->with([
    'glucose in mmol/L' => [['log_type' => 'glucose', 'glucose_value' => 6.7, 'glucose_reading_type' => 'fasting'], 'bloodGlucose', 121.0, 'glucose_reading_type', 'fasting'],
    'food' => [['log_type' => 'food', 'carbs_grams' => 45, 'protein_grams' => 20, 'fat_grams' => 10, 'calories' => 420, 'fiber_grams' => 6, 'meal_type' => 'lunch'], 'dietaryEnergy', 420.0, 'meal_type', 'lunch'],
    'insulin' => [['log_type' => 'insulin', 'insulin_units' => 10, 'insulin_type' => 'bolus'], 'insulin', 10.0, 'insulin_type', 'bolus'],
    'medication' => [['log_type' => 'meds', 'medication_name' => 'Metformin', 'medication_dosage' => '500mg'], 'medication', 1.0, 'medication_name', 'Metformin'],
    'vitals' => [['log_type' => 'vitals', 'weight' => 72.5, 'blood_pressure_systolic' => 120, 'blood_pressure_diastolic' => 80], 'weight', 72.5, 'blood_pressure_diastolic', 80],
    'exercise' => [['log_type' => 'exercise', 'exercise_type' => 'Walking', 'exercise_duration_minutes' => 30], 'exerciseMinutes', 30.0, 'exercise_type', 'Walking'],
]);

it('keeps glucose as entered for people who read mg/dL', function (): void {
    UserProfile::factory()->for($this->user)->create(['units_preference' => GlucoseUnit::MgDl]);
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.health-entries.store'), ['log_type' => 'glucose', 'glucose_value' => 120, 'glucose_reading_type' => 'random', 'measured_at' => now()->toIso8601String()])
        ->assertCreated()
        ->assertJsonPath('glucose_unit', 'mg/dL')
        ->assertJsonPath('entry.glucose_value', 120);
});

it('logs a meal with its ingredients as one grouped entry', function (): void {
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.health-entries.store'), [
        'log_type' => 'food',
        'meal_type' => 'breakfast',
        'measured_at' => '2026-10-18T23:00:00Z',
        'items' => [
            ['name' => 'Oatmeal', 'portion' => '1 bowl', 'grams' => 250, 'calories' => 300, 'protein' => 10, 'carbs' => 54, 'fat' => 5, 'fiber' => 8, 'provenance' => 'user'],
            ['name' => 'Banana', 'portion' => '1 medium', 'calories' => 105, 'protein' => 1.3, 'carbs' => 27, 'fat' => 0.4, 'provenance' => 'user'],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('date', '2026-10-19')
        ->assertJsonPath('entry.calories', 405)
        ->assertJsonPath('entry.carbs_grams', 81)
        ->assertJsonPath('entry.fiber_grams', 8)
        ->assertJsonPath('entry.notes', 'Oatmeal, Banana')
        ->assertJsonPath('entry.food_analysis.items.1.name', 'Banana');

    expect(HealthSyncSample::query()->where('user_id', $this->user->id)->pluck('group_id')->unique())->toHaveCount(1);
});

it('refreshes the daily totals of the day the entry lands on', function (): void {
    Queue::fake();
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.health-entries.store'), ['log_type' => 'insulin', 'insulin_units' => 4, 'insulin_type' => 'basal', 'measured_at' => '2026-10-18T16:30:00Z'])
        ->assertCreated();

    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => $job->uniqueId() === $this->user->id.':2026-10-18');
});

it('rejects entries it cannot save', function (array $payload, string $field): void {
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.health-entries.store'), [...$payload, 'measured_at' => now()->toIso8601String()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect(HealthSyncSample::query()->count())->toBe(0);
})->with([
    'unknown log type' => [['log_type' => 'sleep'], 'log_type'],
    'glucose above the mmol/L range' => [['log_type' => 'glucose', 'glucose_value' => 40, 'glucose_reading_type' => 'fasting'], 'glucose_value'],
    'glucose without a reading type' => [['log_type' => 'glucose', 'glucose_value' => 6.1], 'glucose_reading_type'],
    'food without any nutrition' => [['log_type' => 'food'], 'carbs_grams'],
    'fractional calories' => [['log_type' => 'food', 'calories' => 120.5], 'calories'],
    'empty vitals' => [['log_type' => 'vitals'], 'vitals'],
    'meal heavier than the daily cap' => [['log_type' => 'food', 'items' => [['name' => 'Feast', 'calories' => 4000], ['name' => 'Dessert', 'calories' => 1500]]], 'items'],
    'medication without a name' => [['log_type' => 'meds'], 'medication_name'],
]);
