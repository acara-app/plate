<?php

declare(strict_types=1);

use App\Ai\Tools\LogHealthEntry;
use App\Enums\HealthEntrySource;
use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

it('has correct name and description', function (): void {
    $tool = new LogHealthEntry;

    expect($tool->name())->toBe('log_health_entry');
    expect($tool->description())->toContain('Log a health entry');
});

it('has valid schema with enum constraints', function (): void {
    $tool = new LogHealthEntry;
    $schema = $tool->schema(new TestJsonSchema);

    expect($schema)
        ->toHaveKey('log_type')
        ->toHaveKey('glucose_value')
        ->toHaveKey('carbs_grams')
        ->toHaveKey('notes')
        ->toHaveKey('weight')
        ->toHaveKey('bp_systolic')
        ->toHaveKey('bp_diastolic')
        ->toHaveKey('exercise_type')
        ->toHaveKey('exercise_duration_minutes')
        ->toHaveKey('measured_at');
});

it('returns error if user is not authenticated', function (): void {
    Auth::shouldReceive('user')->andReturn(null);

    $tool = new LogHealthEntry;
    $request = new Request(['log_type' => 'glucose', 'glucose_value' => 120]);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('error', 'User not authenticated');
});

it('logs a glucose entry successfully', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = new LogHealthEntry;
    $request = new Request([
        'log_type' => 'glucose',
        'glucose_value' => 140,
        'glucose_reading_type' => 'fasting',
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)
        ->toHaveKey('success', true)
        ->toHaveKey('entry_id')
        ->toHaveKey('message');

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::BloodGlucose->value)
        ->first();

    expect($sample->value)->toBe(140.0);
    expect($sample->entry_source)->toBe(HealthEntrySource::Chat);
});

it('logs a food entry with notes', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = new LogHealthEntry;
    $request = new Request([
        'log_type' => 'food',
        'carbs_grams' => 45,
        'notes' => 'tsuivan',
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('success', true);

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::Carbohydrates->value)
        ->first();

    expect((float) $sample->value)->toBe(45.0);
    expect($sample->notes)->toBe('tsuivan');
    expect($sample->entry_source)->toBe(HealthEntrySource::Chat);
});

it('logs a vitals entry with weight', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = new LogHealthEntry;
    $request = new Request([
        'log_type' => 'vitals',
        'weight' => 81.65,
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('success', true);

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::Weight->value)
        ->first();

    expect($sample->value)->toBe(81.65);
    expect($sample->entry_source)->toBe(HealthEntrySource::Chat);
});

it('logs an exercise entry', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = new LogHealthEntry;
    $request = new Request([
        'log_type' => 'exercise',
        'exercise_type' => 'walking',
        'exercise_duration_minutes' => 30,
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('success', true);

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::ExerciseMinutes->value)
        ->first();

    expect($sample->value)->toBe(30.0);
    expect($sample->entry_source)->toBe(HealthEntrySource::Chat);
    expect($sample->metadata['exercise_type'])->toBe('walking');
});

it('logs a food entry with only notes and no macros', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = new LogHealthEntry;
    $request = new Request([
        'log_type' => 'food',
        'notes' => 'apple',
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('success', true);

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::DietaryEnergy->value)
        ->first();

    expect($sample->value)->toBe(0.0);
    expect($sample->notes)->toBe('apple');
});

it('returns error when vitals entry has no data', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = new LogHealthEntry;
    $request = new Request([
        'log_type' => 'vitals',
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)
        ->toHaveKey('error')
        ->toHaveKey('hint');
});

it('logs a medication entry', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = new LogHealthEntry;
    $request = new Request([
        'log_type' => 'meds',
        'medication_name' => 'metformin',
        'medication_dosage' => '500mg',
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('success', true);

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::Medication->value)
        ->first();

    expect($sample->metadata['medication_name'])->toBe('metformin');
    expect($sample->metadata['medication_dosage'])->toBe('500mg');
    expect($sample->entry_source)->toBe(HealthEntrySource::Chat);
});
