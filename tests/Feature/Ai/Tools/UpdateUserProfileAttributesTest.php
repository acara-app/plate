<?php

declare(strict_types=1);

use App\Ai\Tools\UpdateUserProfileAttributes;
use App\Enums\AllergySeverity;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserProfileAttribute;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

covers(UpdateUserProfileAttributes::class);

beforeEach(function (): void {
    $this->tool = new UpdateUserProfileAttributes;
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('update_user_profile_attributes')
        ->and($this->tool->description())->toContain('dietary preferences');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;
    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['action', 'category', 'value', 'severity', 'notes', 'metadata']);
});

it('returns error if user is not authenticated', function (): void {
    $request = new Request(['action' => 'list']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'User not authenticated');
});

it('lists empty attributes for new user', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request(['action' => 'list']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue()
        ->attributes->toBeEmpty();
});

it('lists existing attributes', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->for($user)->create();
    UserProfileAttribute::factory()->allergy('Peanuts')->create(['user_profile_id' => $profile->id]);
    UserProfileAttribute::factory()->healthCondition('Type 2 Diabetes')->create(['user_profile_id' => $profile->id]);
    $this->actingAs($user);

    $request = new Request(['action' => 'list']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue()
        ->attributes->toHaveCount(2);
});

it('adds an allergy attribute', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request([
        'action' => 'add',
        'category' => 'allergy',
        'value' => 'Peanuts',
        'severity' => 'severe',
        'notes' => 'Anaphylactic reaction',
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue()
        ->message->toContain('Peanuts');

    expect($json['attribute'])
        ->category->toBe('allergy')
        ->value->toBe('Peanuts')
        ->severity->toBe('severe')
        ->notes->toBe('Anaphylactic reaction');
});

it('adds a medication attribute with metadata', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request([
        'action' => 'add',
        'category' => 'medication',
        'value' => 'Metformin',
        'metadata' => [
            'dosage' => '500mg',
            'frequency' => 'twice daily',
            'purpose' => 'Blood sugar control',
        ],
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue();

    expect($json['attribute']['metadata'])
        ->dosage->toBe('500mg')
        ->frequency->toBe('twice daily')
        ->purpose->toBe('Blood sugar control');
});

it('returns error when adding without category or value', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request(['action' => 'add', 'category' => 'allergy']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error');
});

it('returns error for invalid category', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request(['action' => 'add', 'category' => 'invalid', 'value' => 'Test']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error')
        ->and($json['error'])->toContain('Invalid category');
});

it('updates an existing attribute', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->for($user)->create();
    UserProfileAttribute::factory()->allergy('Peanuts', AllergySeverity::Mild)->create([
        'user_profile_id' => $profile->id,
    ]);
    $this->actingAs($user);

    $request = new Request([
        'action' => 'update',
        'category' => 'allergy',
        'value' => 'Peanuts',
        'severity' => 'severe',
        'notes' => 'Upgraded severity',
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue();

    expect($json['attribute'])
        ->severity->toBe('severe')
        ->notes->toBe('Upgraded severity');
});

it('returns error when updating non-existent attribute', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request([
        'action' => 'update',
        'category' => 'allergy',
        'value' => 'Nonexistent',
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error')
        ->and($json['error'])->toContain('Attribute not found');
});

it('removes an attribute', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->for($user)->create();
    UserProfileAttribute::factory()->healthCondition('Type 2 Diabetes')->create([
        'user_profile_id' => $profile->id,
    ]);
    $this->actingAs($user);

    $request = new Request([
        'action' => 'remove',
        'category' => 'health_condition',
        'value' => 'Type 2 Diabetes',
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->success->toBeTrue()
        ->and(UserProfileAttribute::query()->where('user_profile_id', $profile->id)->count())->toBe(0);
});

it('returns error when removing non-existent attribute', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request([
        'action' => 'remove',
        'category' => 'allergy',
        'value' => 'Nonexistent',
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error')
        ->and($json['error'])->toContain('Attribute not found');
});

it('creates profile if it does not exist', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect($user->profile)->toBeNull();

    $request = new Request([
        'action' => 'add',
        'category' => 'allergy',
        'value' => 'Shellfish',
        'severity' => 'moderate',
    ]);
    $this->tool->handle($request);

    $user->refresh();
    expect($user->profile)->not->toBeNull()
        ->and($user->profile->attributes)->toHaveCount(1);
});

it('handles unknown action', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request(['action' => 'invalid_action']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error')
        ->and($json['error'])->toContain('Unknown action');
});

it('uses updateOrCreate to prevent duplicates on add', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->for($user)->create();
    UserProfileAttribute::factory()->allergy('Peanuts', AllergySeverity::Mild)->create([
        'user_profile_id' => $profile->id,
    ]);
    $this->actingAs($user);

    $request = new Request([
        'action' => 'add',
        'category' => 'allergy',
        'value' => 'Peanuts',
        'severity' => 'severe',
    ]);
    $this->tool->handle($request);

    expect(UserProfileAttribute::query()->where('user_profile_id', $profile->id)->count())->toBe(1);

    $attr = UserProfileAttribute::query()->where('user_profile_id', $profile->id)->first();
    expect($attr->severity)->toBe(AllergySeverity::Severe);
});

it('returns error when updating without category', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request([
        'action' => 'update',
        'value' => 'Peanuts',
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error')
        ->and($json['error'])->toContain('Both "category" and "value" are required');
});

it('returns error when removing without category', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request([
        'action' => 'remove',
        'value' => 'Peanuts',
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error')
        ->and($json['error'])->toContain('Both "category" and "value" are required');
});

it('updates metadata when provided', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->for($user)->create();
    UserProfileAttribute::factory()->healthCondition('Type 2 Diabetes')->create([
        'user_profile_id' => $profile->id,
        'metadata' => ['safety_level' => 'warning'],
    ]);
    $this->actingAs($user);

    $request = new Request([
        'action' => 'update',
        'category' => 'health_condition',
        'value' => 'Type 2 Diabetes',
        'metadata' => ['safety_level' => 'critical', 'new_field' => 'value'],
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue();

    expect($json['attribute']['metadata'])
        ->safety_level->toBe('critical')
        ->new_field->toBe('value');
});
