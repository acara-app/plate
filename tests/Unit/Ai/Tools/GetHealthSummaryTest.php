<?php

declare(strict_types=1);

use App\Ai\Tools\GetHealthSummary;
use App\Models\HealthSyncSample;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

covers(GetHealthSummary::class);

beforeEach(function (): void {
    $this->tool = new GetHealthSummary();
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('get_health_summary')
        ->and($this->tool->description())->toContain('aggregated daily summaries');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;

    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['type', 'days', 'date']);
});

it('returns error if user is not authenticated', function (): void {
    $request = new Request(['type' => 'steps']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'User not authenticated');
});

it('returns step count aggregated by day', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->stepCount()->create([
        'user_id' => $user->id,
        'value' => 3000,
        'measured_at' => today()->addHours(8),
    ]);

    HealthSyncSample::factory()->stepCount()->create([
        'user_id' => $user->id,
        'value' => 5000,
        'measured_at' => today()->addHours(14),
    ]);

    $request = new Request(['type' => 'steps', 'days' => 1]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['summaries'])->toHaveCount(1)
        ->and($json['summaries'][0]['type'])->toBe('stepCount')
        ->and((float) $json['summaries'][0]['total'])->toBe(8000.0)
        ->and($json['summaries'][0]['count'])->toBe(2);
});

it('returns heart rate with avg min max', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->heartRate()->create([
        'user_id' => $user->id,
        'value' => 60,
        'measured_at' => now(),
    ]);

    HealthSyncSample::factory()->heartRate()->create([
        'user_id' => $user->id,
        'value' => 80,
        'measured_at' => now(),
    ]);

    HealthSyncSample::factory()->heartRate()->create([
        'user_id' => $user->id,
        'value' => 100,
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'heart_rate', 'days' => 1]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['summaries'])->toHaveCount(1)
        ->and((float) $json['summaries'][0]['avg'])->toBe(80.0)
        ->and((float) $json['summaries'][0]['min'])->toBe(60.0)
        ->and((float) $json['summaries'][0]['max'])->toBe(100.0)
        ->and((float) $json['summaries'][0]['total'])->toBe(240.0);
});

it('returns active energy summed by day', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->activeEnergy()->create([
        'user_id' => $user->id,
        'value' => 150.5,
        'measured_at' => now(),
    ]);

    HealthSyncSample::factory()->activeEnergy()->create([
        'user_id' => $user->id,
        'value' => 200.0,
        'measured_at' => now(),
    ]);

    $request = new Request(['type' => 'active_energy', 'days' => 1]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['summaries'])->toHaveCount(1)
        ->and($json['summaries'][0]['type'])->toBe('activeEnergy')
        ->and($json['summaries'][0]['total'])->toBe(350.5);
});

it('filters by date range correctly', function (): void {
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

    expect($json['summaries'])->toHaveCount(2);
});

it('returns empty result when no data exists', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['type' => 'steps', 'days' => 7]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['summaries'])->toBeEmpty();
});

it('returns all types including healthkit and entry data when type is all', function (): void {
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

    $request = new Request(['type' => 'all', 'days' => 1]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    $types = array_column($json['summaries'], 'type');

    expect($types)->toContain('stepCount')
        ->and($types)->toContain('bloodGlucose');
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

    $request = new Request(['type' => 'steps', 'days' => 1]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['summaries'])->toBeEmpty();
});

it('defaults to 7 days', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    HealthSyncSample::factory()->stepCount()->create([
        'user_id' => $user->id,
        'value' => 5000,
        'measured_at' => now()->subDays(5),
    ]);

    $request = new Request(['type' => 'steps']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['summaries'])->toHaveCount(1)
        ->and($json['date_range']['from'])->toBe(now()->subDays(6)->toDateString());
});
