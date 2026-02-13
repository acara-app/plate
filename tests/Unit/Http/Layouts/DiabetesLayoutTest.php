<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Layouts;

use App\Http\Layouts\DiabetesLayout;
use App\Models\HealthEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard data uses default time period when invalid period provided', function (): void {
    $user = User::factory()->create();

    $data = DiabetesLayout::dashboardData($user, 'invalid_period');

    expect($data['timePeriod'])->toBe('30d');
});

test('calculate weight stats determines upward trend', function (): void {
    $user = User::factory()->create();

    // Create log with lower weight earlier
    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'weight' => 80.0,
        'measured_at' => now()->subDays(2),
    ]);

    // Create log with higher weight later
    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'weight' => 81.0,
        'measured_at' => now(),
    ]);

    $data = DiabetesLayout::dashboardData($user);
    $stats = $data['summary']['weightStats'];

    expect($stats['trend'])->toBe('up')
        ->and($stats['diff'])->toBe(1.0);
});

test('calculate weight stats determines downward trend', function (): void {
    $user = User::factory()->create();

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'weight' => 82.0,
        'measured_at' => now()->subDays(2),
    ]);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'weight' => 81.0,
        'measured_at' => now(),
    ]);

    $data = DiabetesLayout::dashboardData($user);
    $stats = $data['summary']['weightStats'];

    expect($stats['trend'])->toBe('down')
        ->and($stats['diff'])->toBe(1.0);
});

test('calculate weight stats determines stable trend', function (): void {
    $user = User::factory()->create();

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'weight' => 81.0,
        'measured_at' => now()->subDays(2),
    ]);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'weight' => 81.0,
        'measured_at' => now(),
    ]);

    $data = DiabetesLayout::dashboardData($user);
    $stats = $data['summary']['weightStats'];

    expect($stats['trend'])->toBe('stable')
        ->and($stats['diff'])->toBe(0.0);
});

test('calculate streak continues if log exists yesterday but not today', function (): void {
    $user = User::factory()->create();

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'measured_at' => now()->subDay(),
    ]);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'measured_at' => now()->subDays(2),
    ]);

    $data = DiabetesLayout::dashboardData($user);
    $stats = $data['summary']['streakStats'];

    expect($stats['currentStreak'])->toBe(2);
});
