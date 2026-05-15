<?php

declare(strict_types=1);

use App\Ai\Tools\GetUserProfile;
use App\Enums\AllergySeverity;
use App\Enums\BloodType;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserProfileAttribute;
use Laravel\Ai\Tools\Request;
use Pest\Mixins\Expectation;
use Tests\Helpers\TestJsonSchema;

covers(GetUserProfile::class);

beforeEach(function (): void {
    $this->tool = resolve(GetUserProfile::class);
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('get_user_profile')
        ->and($this->tool->description())->toContain("Retrieve the current user's AI-safe profile information")
        ->and($this->tool->description())->toContain('smallest relevant section');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;

    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('section');
});

it('returns error if user is not authenticated', function (): void {
    $request = new Request(['section' => 'all']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'User not authenticated');
});

it('returns full profile when section is all', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'onboarding_completed' => true,
        'household_context' => 'Cooking for a child with a peanut allergy.',
    ]);

    $request = new Request(['section' => 'all']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['profile'])->toHaveKeys([
            'biometrics',
            'dietary_preferences',
            'goals',
            'health_conditions',
            'medications',
            'household',
        ])
        ->and($json['profile']['biometrics'])->toHaveKey('age', 30)
        ->and($json['profile']['household'])->toHaveKey('summary', 'Cooking for a child with a peanut allergy.');
});

it('returns specific section data', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
    ]);

    $request = new Request(['section' => 'biometrics']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['section'])->toBe('biometrics')
        ->and($json['data'])->toHaveKey('age', 30);
});

it('returns expanded profile sections individually', function (string $section, callable $assert): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'household_context' => 'Cooking for two adults.',
    ]);
    UserProfileAttribute::factory()->dietaryPattern('Vegetarian')->create([
        'user_profile_id' => $profile->id,
    ]);
    UserProfileAttribute::factory()->healthCondition('Type 2 Diabetes')->create([
        'user_profile_id' => $profile->id,
    ]);
    UserProfileAttribute::factory()->medication('Metformin')->create([
        'user_profile_id' => $profile->id,
    ]);

    $result = $this->tool->handle(new Request(['section' => $section]));
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['section'])->toBe($section);

    $assert($json['data']);
})->with([
    'dietary preferences' => [
        'dietary_preferences',
        fn (array $data): Expectation => expect($data[0])->toHaveKey('name', 'Vegetarian'),
    ],
    'goals' => [
        'goals',
        fn (array $data): Expectation => expect($data)->toHaveKey('primary_goal', 'weight_loss'),
    ],
    'health conditions' => [
        'health_conditions',
        fn (array $data): Expectation => expect($data[0])->toHaveKey('name', 'Type 2 Diabetes'),
    ],
    'medications' => [
        'medications',
        fn (array $data): Expectation => expect($data[0])->toHaveKey('name', 'Metformin'),
    ],
    'household' => [
        'household',
        fn (array $data): Expectation => expect($data)->toHaveKey('summary', 'Cooking for two adults.'),
    ],
]);

it('handles missing section error', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
    ]);

    $request = new Request(['section' => 'invalid_section']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error')
        ->and($json['error'])->toContain("Section 'invalid_section' not found")
        ->and($json['error'])->toContain('health_conditions');
});

it('returns safety section with allergies conditions medications and household constraints', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'household_context' => 'My daughter has a peanut allergy.',
    ]);

    UserProfileAttribute::factory()->allergy('Peanuts', AllergySeverity::Severe)->create([
        'user_profile_id' => $profile->id,
        'notes' => 'Avoid cross-contact.',
    ]);
    UserProfileAttribute::factory()->healthCondition('Type 2 Diabetes')->create([
        'user_profile_id' => $profile->id,
        'metadata' => ['safety_level' => 'critical'],
    ]);
    UserProfileAttribute::factory()->medication('Metformin', [
        'dosage' => '500mg',
        'frequency' => 'twice daily',
        'purpose' => 'Blood sugar control',
    ])->create([
        'user_profile_id' => $profile->id,
    ]);

    $request = new Request(['section' => 'safety']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['section'])->toBe('safety')
        ->and($json['data'])->toHaveKeys(['allergies', 'health_conditions', 'medications', 'household'])
        ->and($json['data']['allergies'][0])->toMatchArray([
            'category' => 'allergy',
            'name' => 'Peanuts',
            'severity' => 'severe',
            'notes' => 'Avoid cross-contact.',
        ])
        ->and($json['data']['health_conditions'][0])->toMatchArray([
            'category' => 'health_condition',
            'name' => 'Type 2 Diabetes',
            'metadata' => ['safety_level' => 'critical'],
        ])
        ->and($json['data']['medications'][0])->toMatchArray([
            'category' => 'medication',
            'name' => 'Metformin',
            'metadata' => [
                'dosage' => '500mg',
                'frequency' => 'twice daily',
                'purpose' => 'Blood sugar control',
            ],
        ])
        ->and($json['data']['household'])->toHaveKey('summary', 'My daughter has a peanut allergy.');
});

it('does not expose unrelated sensitive profile fields or model internals', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'date_of_birth' => '1996-04-04',
        'blood_type' => BloodType::APositive,
    ]);
    UserProfileAttribute::factory()->medication('Metformin')->create([
        'user_profile_id' => $profile->id,
    ]);

    $result = $this->tool->handle(new Request(['section' => 'all']));
    $json = json_decode((string) $result, true);

    expect($json['profile']['biometrics'])
        ->not->toHaveKeys(['date_of_birth', 'blood_type'])
        ->and($json['profile']['medications'][0])
        ->not->toHaveKeys(['id', 'user_profile_id', 'created_at', 'updated_at']);
});
