<?php

declare(strict_types=1);

use App\Ai\Tools\GetHealthEntries;
use App\Enums\HealthEntrySource;
use App\Models\HealthEntry;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

beforeEach(function (): void {
    $this->tool = new GetHealthEntries();
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('get_health_entries')
        ->and($this->tool->description())->toContain('logged health entries');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;

    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['date', 'days', 'type']);
});

it('returns error if user is not authenticated', function (): void {
    $request = new Request;
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'User not authenticated');
});

it('returns entries for today', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'carbs_grams' => 27,
        'measured_at' => now(),
        'source' => HealthEntrySource::Telegram,
    ]);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'carbs_grams' => 21,
        'measured_at' => now(),
        'source' => HealthEntrySource::Telegram,
    ]);

    $request = new Request;
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['total_entries'])->toBe(2)
        ->and($json['entries'])->toHaveCount(2)
        ->and($json['entries'][0])->toHaveKey('carbs_grams');
});

it('returns entries filtered by food type', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'carbs_grams' => 27,
        'measured_at' => now(),
    ]);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'glucose_value' => 140.0,
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'food']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['total_entries'])->toBe(1)
        ->and($json['entries'][0])->toHaveKey('carbs_grams', 27);
});

it('returns entries for a date range', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'carbs_grams' => 50,
        'measured_at' => now()->subDays(2),
    ]);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'carbs_grams' => 30,
        'measured_at' => now(),
    ]);

    $request = new Request(['days' => 3]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['total_entries'])->toBe(2);
});

it('returns empty array when no entries exist', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request;
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['total_entries'])->toBe(0)
        ->and($json['entries'])->toBeEmpty();
});

it('does not return entries from other users', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    HealthEntry::factory()->create([
        'user_id' => $otherUser->id,
        'carbs_grams' => 50,
        'measured_at' => now(),
    ]);

    $request = new Request;
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['total_entries'])->toBe(0);
});

it('formats glucose entries correctly', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'glucose_value' => 140.5,
        'glucose_reading_type' => 'post-meal',
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'glucose']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['entries'][0])->toHaveKey('glucose_value', 140.5)
        ->and($json['entries'][0])->toHaveKey('glucose_reading_type', 'post-meal');
});

it('formats vitals entries correctly - weight', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'weight' => 84.5,
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'vitals']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['entries'][0])->toHaveKey('weight_kg', 84.5);
});

it('formats vitals entries correctly - blood pressure', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'blood_pressure_systolic' => 120,
        'blood_pressure_diastolic' => 80,
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'vitals']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['entries'][0])->toHaveKey('blood_pressure', '120/80');
});

it('formats vitals entries correctly - a1c', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'a1c_value' => 7.5,
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'vitals']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['entries'][0])->toHaveKey('a1c_value', 7.5);
});

it('formats exercise entries correctly', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'exercise_type' => 'running',
        'exercise_duration_minutes' => 30,
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'exercise']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['entries'][0])->toHaveKey('exercise', 'running')
        ->and($json['entries'][0])->toHaveKey('exercise_duration_minutes', 30);
});

it('formats medication entries correctly - insulin', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'insulin_units' => 10.5,
        'insulin_type' => 'bolus',
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'medication']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['entries'][0])->toHaveKey('insulin_units', 10.5)
        ->and($json['entries'][0])->toHaveKey('insulin_type', 'bolus');
});

it('formats medication entries correctly - oral medication', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'medication_name' => 'Metformin',
        'medication_dosage' => '500mg',
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'medication']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['entries'][0])->toHaveKey('medication', 'Metformin 500mg');
});

it('formats notes correctly', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'notes' => 'Felt dizzy after lunch',
        'measured_at' => now(),
    ]);

    $request = new Request;
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['entries'][0])->toHaveKey('notes', 'Felt dizzy after lunch');
});

it('returns food entries with all macros', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'carbs_grams' => 50.0,
        'protein_grams' => 20.0,
        'fat_grams' => 15.0,
        'calories' => 400,
        'notes' => 'tsuivan',
        'measured_at' => now(),
        'source' => HealthEntrySource::Telegram,
    ]);

    $request = new Request(['type' => 'food']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['entries'][0])->toHaveKey('carbs_grams', 50.0)
        ->and($json['entries'][0])->toHaveKey('protein_grams', 20.0)
        ->and($json['entries'][0])->toHaveKey('fat_grams', 15.0)
        ->and($json['entries'][0])->toHaveKey('calories', 400)
        ->and($json['entries'][0])->toHaveKey('food_name', 'tsuivan');
});
