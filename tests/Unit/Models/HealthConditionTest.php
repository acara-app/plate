<?php

declare(strict_types=1);

use App\Models\HealthCondition;

it('has correct casts', function (): void {
    $condition = HealthCondition::factory()->create();

    expect($condition->id)->toBeInt()
        ->and($condition->order)->toBeInt()
        ->and($condition->name)->toBeString()
        ->and($condition->recommended_nutrients)->toBeArray()
        ->and($condition->nutrients_to_limit)->toBeArray()
        ->and($condition->created_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($condition->updated_at)->toBeInstanceOf(DateTimeInterface::class);
});

it('can be ordered by order column', function (): void {
    HealthCondition::factory()->create(['name' => 'First', 'order' => 3]);
    HealthCondition::factory()->create(['name' => 'Second', 'order' => 1]);
    HealthCondition::factory()->create(['name' => 'Third', 'order' => 2]);

    $ordered = HealthCondition::query()->orderBy('order')->get();

    expect($ordered->first()->name)->toBe('Second')
        ->and($ordered->get(1)->name)->toBe('Third')
        ->and($ordered->last()->name)->toBe('First');
});

it('has fillable order attribute', function (): void {
    $condition = HealthCondition::factory()->create([
        'name' => 'Test Condition',
        'order' => 5,
    ]);

    expect($condition->order)->toBe(5);
});

it('can access notes from pivot when relation is loaded', function (): void {
    $condition = HealthCondition::factory()->create();
    $user = App\Models\User::factory()->create();
    $profile = App\Models\UserProfile::factory()->create(['user_id' => $user->id]);

    $profile->healthConditions()->attach($condition, ['notes' => 'Test notes']);

    $loadedCondition = $profile->healthConditions()->first();

    expect($loadedCondition->notes)->toBe('Test notes');
});

it('returns null for notes when pivot is not loaded', function (): void {
    $condition = HealthCondition::factory()->create();

    expect($condition->notes)->toBeNull();
});
