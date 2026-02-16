<?php

declare(strict_types=1);

use App\Ai\Tools\SuggestWorkoutRoutine;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

beforeEach(function (): void {
    $this->tool = new SuggestWorkoutRoutine;
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('suggest_workout_routine')
        ->and($this->tool->description())->toContain('Suggest personalized workout routines');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;

    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['focus', 'fitness_level']);
});

it('returns error if user is not authenticated', function (): void {
    $request = new Request(['focus' => 'strength']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'User not authenticated');
});

it('generates strength routine for beginner', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['focus' => 'strength', 'fitness_level' => 'beginner']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['focus'])->toBe('strength')
        ->and($json['fitness_level'])->toBe('beginner')
        ->and($json['workouts']['intensity']['rest'])->toBe('60-90 sec')
        ->and($json['workouts']['schedule'])->toHaveKey('day_1');
});

it('generates strength routine for intermediate', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['focus' => 'strength', 'fitness_level' => 'intermediate']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['focus'])->toBe('strength')
        ->and($json['fitness_level'])->toBe('intermediate')
        ->and($json['workouts']['intensity']['rest'])->toBe('45-60 sec');
});

it('generates strength routine for advanced', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['focus' => 'strength', 'fitness_level' => 'advanced']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['focus'])->toBe('strength')
        ->and($json['fitness_level'])->toBe('advanced')
        ->and($json['workouts']['intensity']['rest'])->toBe('30-45 sec');
});

it('generates cardio routine for advanced', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['focus' => 'cardio', 'fitness_level' => 'advanced']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['focus'])->toBe('cardio')
        ->and($json['fitness_level'])->toBe('advanced')
        ->and($json['workouts']['schedule'])->toHaveKey('day_1');
});

it('generates flexibility routine', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['focus' => 'flexibility', 'fitness_level' => 'beginner']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['focus'])->toBe('flexibility')
        ->and($json['workouts']['schedule']['day_1']['title'])->toBe('Full Body Stretch');
});

it('generates general routine by default', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['focus' => 'general', 'fitness_level' => 'intermediate']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['focus'])->toBe('general')
        ->and($json['workouts']['schedule'])->toHaveKeys(['day_1', 'day_2']);
});
