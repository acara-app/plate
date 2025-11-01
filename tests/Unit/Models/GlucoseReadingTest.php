<?php

declare(strict_types=1);

use App\Enums\ReadingType;
use App\Models\GlucoseReading;
use App\Models\User;

it('has correct casts', function (): void {
    $reading = GlucoseReading::factory()->create();

    expect($reading->casts())->toBeArray()
        ->toHaveKeys(['id', 'user_id', 'reading_value', 'reading_type', 'measured_at']);
});

it('belongs to a user', function (): void {
    $user = User::factory()->create();
    $reading = GlucoseReading::factory()->create(['user_id' => $user->id]);

    expect($reading->user)->toBeInstanceOf(User::class)
        ->and($reading->user->id)->toBe($user->id);
});

it('casts reading type to enum', function (): void {
    $reading = GlucoseReading::factory()->create([
        'reading_type' => ReadingType::Fasting,
    ]);

    expect($reading->reading_type)->toBeInstanceOf(ReadingType::class);
});
