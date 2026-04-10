<?php

declare(strict_types=1);

use App\Ai\Tools\UpdateHouseholdContext;
use App\Models\User;
use App\Models\UserProfile;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

covers(UpdateHouseholdContext::class);

beforeEach(function (): void {
    $this->tool = new UpdateHouseholdContext;
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('update_household_context')
        ->and($this->tool->description())->toContain('household');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;
    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['action', 'household_context']);
});

it('returns error if user is not authenticated', function (): void {
    $request = new Request(['action' => 'get']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'User not authenticated');
});

it('retrieves empty household context', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['household_context' => null]);
    $this->actingAs($user);

    $request = new Request(['action' => 'get']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue()
        ->household_context->toBeNull()
        ->has_household_info->toBeFalse();
});

it('retrieves existing household context', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'household_context' => 'My husband and two kids',
    ]);
    $this->actingAs($user);

    $request = new Request(['action' => 'get']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue()
        ->household_context->toBe('My husband and two kids')
        ->has_household_info->toBeTrue();
});

it('updates household context', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $context = 'My husband Bataa is 38, has type 2 diabetes. Kids: Tana (12, girl, peanut allergy).';
    $request = new Request([
        'action' => 'update',
        'household_context' => $context,
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue()
        ->household_context->toBe($context);

    expect($user->profile->refresh()->household_context)->toBe($context);
});

it('returns error when updating without household_context', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request(['action' => 'update']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error');
});

it('truncates household context to 2000 characters', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request([
        'action' => 'update',
        'household_context' => str_repeat('a', 2500),
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->success->toBeTrue()
        ->and(mb_strlen((string) $user->profile->refresh()->household_context))->toBe(2000);
});

it('returns error for unknown action', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request(['action' => 'delete']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error');
});

it('auto-creates profile when none exists', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['action' => 'get']);
    $this->tool->handle($request);

    expect($user->profile()->exists())->toBeTrue();
});
