<?php

declare(strict_types=1);

use App\Ai\Tools\SuggestWellnessRoutine;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

beforeEach(function (): void {
    $this->tool = new SuggestWellnessRoutine;
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('suggest_wellness_routine')
        ->and($this->tool->description())->toContain('Suggest personalized wellness routines');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;

    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('focus');
});

it('returns error if user is not authenticated', function (): void {
    $request = new Request(['focus' => 'general']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'User not authenticated');
});

it('generates sleep routine', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['focus' => 'sleep']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['focus'])->toBe('sleep')
        ->and($json['routines']['routine'])->toHaveKey('evening');
});

it('generates stress routine', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['focus' => 'stress']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['focus'])->toBe('stress')
        ->and($json['routines']['routine'])->toHaveKey('midday');
});

it('generates hydration routine', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['focus' => 'hydration']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['focus'])->toBe('hydration')
        ->and($json['routines']['routine'])->toHaveKey('morning');
});

it('generates default routine for unknown focus', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['focus' => 'unknown']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['routines']['routine']['morning']['title'])->toBe('Morning Routine');
});

it('generates general routine by default', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['focus' => 'general']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['focus'])->toBe('general')
        ->and($json['routines']['routine'])->toHaveKeys(['morning', 'midday', 'evening']);
});
