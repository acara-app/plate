<?php

declare(strict_types=1);

use App\Ai\Tools\GetHealthData;
use App\Models\HealthSyncSample;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

beforeEach(function (): void {
    $this->tool = new GetHealthData();
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('get_health_data')
        ->and($this->tool->description())->toContain('individual health records');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;

    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['type', 'days', 'date']);
});

it('returns error if user is not authenticated', function (): void {
    $request = new Request(['type' => 'all']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'User not authenticated');
});

it('returns records for today by default', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->stepCount()->create([
        'user_id' => $user->id,
        'value' => 5000,
        'measured_at' => now(),
    ]);

    $request = new Request;
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['total'])->toBe(1)
        ->and($json['records'][0]['type'])->toBe('stepCount')
        ->and((float) $json['records'][0]['value'])->toBe(5000.0);
});

it('filters by category', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->carbohydrates()->create([
        'user_id' => $user->id,
        'value' => 50,
        'measured_at' => now(),
    ]);

    HealthSyncSample::factory()->heartRate()->create([
        'user_id' => $user->id,
        'value' => 72,
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'food']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['total'])->toBe(1)
        ->and($json['records'][0]['type'])->toBe('carbohydrates');
});

it('filters by raw type identifier', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->heartRate()->create([
        'user_id' => $user->id,
        'value' => 72,
        'measured_at' => now(),
    ]);

    HealthSyncSample::factory()->stepCount()->create([
        'user_id' => $user->id,
        'value' => 5000,
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'heartRate']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['total'])->toBe(1)
        ->and($json['records'][0]['type'])->toBe('heartRate');
});

it('excludes user characteristics on type all', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->create([
        'user_id' => $user->id,
        'type_identifier' => 'biologicalSex',
        'value' => 1,
        'unit' => '',
        'measured_at' => now(),
    ]);

    HealthSyncSample::factory()->stepCount()->create([
        'user_id' => $user->id,
        'value' => 5000,
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'all']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    $types = array_column($json['records'], 'type');

    expect($types)->toContain('stepCount')
        ->and($types)->not->toContain('biologicalSex');
});

it('does not return data from other users', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->stepCount()->create([
        'user_id' => $otherUser->id,
        'value' => 9000,
        'measured_at' => now(),
    ]);

    $request = new Request;
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['total'])->toBe(0);
});

it('filters by date range', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->stepCount()->create([
        'user_id' => $user->id,
        'value' => 5000,
        'measured_at' => now()->subDays(2),
    ]);

    HealthSyncSample::factory()->stepCount()->create([
        'user_id' => $user->id,
        'value' => 8000,
        'measured_at' => now(),
    ]);

    HealthSyncSample::factory()->stepCount()->create([
        'user_id' => $user->id,
        'value' => 10000,
        'measured_at' => now()->subDays(10),
    ]);

    $request = new Request(['type' => 'steps', 'days' => 3]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['total'])->toBe(2);
});

it('returns empty result with data_availability when no data exists', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request;
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['total'])->toBe(0)
        ->and($json['records'])->toBeEmpty()
        ->and($json)->toHaveKey('data_availability')
        ->and($json['data_availability']['most_recent_by_type'])->toBeEmpty();
});

it('returns all types including healthkit data when type is all', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->stepCount()->create([
        'user_id' => $user->id,
        'value' => 5000,
        'measured_at' => now(),
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $user->id,
        'value' => 120,
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'all']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    $types = array_column($json['records'], 'type');

    expect($types)->toContain('stepCount')
        ->and($types)->toContain('bloodGlucose');
});

it('includes metadata in records', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $user->id,
        'value' => 140.5,
        'metadata' => ['glucose_reading_type' => 'fasting'],
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'glucose']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['records'][0]['metadata'])->toBe(['glucose_reading_type' => 'fasting']);
});

it('includes data_availability with recent sync data when entries exist outside queried range', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $user->id,
        'value' => 110,
        'measured_at' => now()->subDays(5),
    ]);

    $request = new Request(['type' => 'glucose', 'days' => 1]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['total'])->toBe(0)
        ->and($json)->toHaveKey('data_availability')
        ->and($json['data_availability']['most_recent_by_type'])->toHaveCount(1)
        ->and($json['data_availability']['most_recent_by_type'][0]['type'])->toBe('bloodGlucose')
        ->and($json['data_availability']['most_recent_by_type'][0]['days_ago'])->toBe(5);
});

it('data_availability respects type filter', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $user->id,
        'measured_at' => now()->subDays(3),
    ]);

    HealthSyncSample::factory()->weight()->create([
        'user_id' => $user->id,
        'measured_at' => now()->subDays(2),
    ]);

    $request = new Request(['type' => 'glucose', 'days' => 1]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    $types = array_column($json['data_availability']['most_recent_by_type'], 'type');

    expect($types)->toContain('bloodGlucose')
        ->and($types)->not->toContain('weight');
});

it('does not include data_availability when entries are found', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->stepCount()->create([
        'user_id' => $user->id,
        'value' => 5000,
        'measured_at' => now(),
    ]);

    $request = new Request;
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['total'])->toBe(1)
        ->and($json)->not->toHaveKey('data_availability');
});
