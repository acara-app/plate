<?php

declare(strict_types=1);

use App\Ai\Tools\UpdateUserBiometrics;
use App\Enums\AnimalProductChoice;
use App\Enums\BloodType;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Enums\IntensityChoice;
use App\Enums\Sex;
use App\Models\User;
use App\Models\UserProfile;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

covers(UpdateUserBiometrics::class);

beforeEach(function (): void {
    $this->tool = resolve(UpdateUserBiometrics::class);
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('update_user_biometrics')
        ->and($this->tool->description())->toContain('biometric');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;
    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['action', 'age', 'date_of_birth', 'height', 'height_unit', 'weight', 'weight_unit', 'sex', 'blood_type', 'goal_choice', 'animal_product_choice', 'intensity_choice', 'target_weight', 'target_weight_unit', 'confirmation_token', 'additional_goals']);
});

it('returns error if user is not authenticated', function (): void {
    $request = new Request(['action' => 'get']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'User not authenticated');
});

it('auto-creates profile on get when none exists', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['action' => 'get']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue()
        ->missing_fields->toBeArray();

    expect($user->profile()->exists())->toBeTrue();
});

it('returns current biometrics for existing profile', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'age' => 28,
        'height' => 175,
        'weight' => 70,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss,
    ]);
    $this->actingAs($user);

    $request = new Request(['action' => 'get']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue()
        ->biometrics->age->toBe(28)
        ->biometrics->height->toEqual(175.0)
        ->biometrics->weight->toEqual(70.0)
        ->biometrics->sex->toBe('male')
        ->biometrics->goal_choice->toBe('weight_loss');
});

it('updates biometric fields', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request([
        'action' => 'update',
        'age' => 28,
        'height' => 175,
        'weight' => 70,
        'sex' => 'male',
        'goal_choice' => 'weight_loss',
        'animal_product_choice' => 'omnivore',
        'intensity_choice' => 'balanced',
        'target_weight' => 65,
        'additional_goals' => 'Run a marathon',
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue()
        ->biometrics->age->toBe(28)
        ->biometrics->weight->toEqual(70.0)
        ->biometrics->sex->toBe('male')
        ->biometrics->goal_choice->toBe('weight_loss')
        ->biometrics->animal_product_choice->toBe('omnivore')
        ->biometrics->intensity_choice->toBe('balanced')
        ->biometrics->target_weight->toEqual(65.0)
        ->biometrics->additional_goals->toBe('Run a marathon');
});

it('auto-creates profile on update when none exists', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request([
        'action' => 'update',
        'age' => 30,
        'weight' => 80,
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue()
        ->biometrics->age->toBe(30)
        ->biometrics->weight->toEqual(80.0);

    expect($user->profile()->exists())->toBeTrue();
});

it('returns error for invalid enum values', function (string $field, string $value): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request([
        'action' => 'update',
        $field => $value,
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error');
})->with([
    'invalid sex' => ['sex', 'invalid'],
    'invalid blood_type' => ['blood_type', 'invalid'],
    'invalid goal_choice' => ['goal_choice', 'invalid'],
    'invalid animal_product_choice' => ['animal_product_choice', 'invalid'],
    'invalid intensity_choice' => ['intensity_choice', 'invalid'],
]);

it('returns error when no valid fields provided for update', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request(['action' => 'update']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'No valid fields provided to update.');
});

it('reports missing fields accurately', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'age' => 28,
        'height' => null,
        'weight' => null,
        'sex' => null,
        'goal_choice' => null,
    ]);
    $this->actingAs($user);

    $request = new Request(['action' => 'get']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['missing_fields'])
        ->toContain('height')
        ->toContain('weight')
        ->toContain('sex')
        ->toContain('goal_choice')
        ->not->toContain('age');
});

it('updates date_of_birth and auto-computes age', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request([
        'action' => 'update',
        'date_of_birth' => '1996-04-04',
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue()
        ->biometrics->date_of_birth->toBe('1996-04-04')
        ->biometrics->age->toBe(30);
});

it('updates blood_type', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request([
        'action' => 'update',
        'blood_type' => 'A+',
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue()
        ->biometrics->blood_type->toBe('A+');
});

it('returns error for invalid date_of_birth', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();
    $this->actingAs($user);

    $request = new Request([
        'action' => 'update',
        'date_of_birth' => 'not-a-date',
    ]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error');
});

it('returns date_of_birth and blood_type in get response', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'date_of_birth' => '1996-04-04',
        'blood_type' => BloodType::OPositive,
    ]);
    $this->actingAs($user);

    $request = new Request(['action' => 'get']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)
        ->success->toBeTrue()
        ->biometrics->date_of_birth->toBe('1996-04-04')
        ->biometrics->blood_type->toBe('O+');
});

it('recomputes the diet type and activity level when the goal changes in chat', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'goal_choice' => GoalChoice::HealthyEating,
        'animal_product_choice' => AnimalProductChoice::Omnivore,
        'intensity_choice' => IntensityChoice::Balanced,
        'calculated_diet_type' => DietType::Balanced,
        'derived_activity_multiplier' => 1.3,
    ]);
    $this->actingAs($user);

    $this->tool->handle(new Request(['action' => 'update', 'goal_choice' => 'weight_loss', 'intensity_choice' => 'aggressive']));

    expect($user->profile()->firstOrFail())
        ->calculated_diet_type->toBe(DietType::Keto)
        ->derived_activity_multiplier->toBe(1.55);
});

function biometricsUpdate(UpdateUserBiometrics $tool, array $arguments): array
{
    return json_decode($tool->handle(new Request(['action' => 'update', ...$arguments])), true);
}

it('converts stated units from chat to the stored kilograms and centimeters', function (array $arguments, array $expected): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 70, 'target_weight' => 65]);
    $this->actingAs($user);

    expect(biometricsUpdate($this->tool, $arguments))->success->toBeTrue()
        ->and($user->profile()->firstOrFail()->only(array_keys($expected)))->toEqual($expected);
})->with([
    'pounds and inches' => [['height' => 70, 'height_unit' => 'in', 'weight' => 200, 'weight_unit' => 'lb'], ['height' => 177.8, 'weight' => 90.72]],
    'metres' => [['height' => 1.75, 'height_unit' => 'm'], ['height' => 175.0]],
    'spelled-out units' => [['weight' => 200, 'weight_unit' => 'lbs', 'height' => 70, 'height_unit' => 'inches'], ['height' => 177.8, 'weight' => 90.72]],
    'weight in pounds with a target in kilograms' => [['weight' => 193, 'weight_unit' => 'lb', 'target_weight' => 80, 'target_weight_unit' => 'kg'], ['weight' => 87.54, 'target_weight' => 80.0]],
    'target in pounds' => [['target_weight' => 180, 'target_weight_unit' => 'lb'], ['target_weight' => 81.65]],
]);

it('rejects values outside the accepted range but still saves the rest', function (array $arguments, string $field): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 70, 'target_weight' => 65, 'age' => 30, 'blood_type' => null]);
    $this->actingAs($user);

    $json = biometricsUpdate($this->tool, [...$arguments, 'blood_type' => 'O+']);

    expect($json)
        ->requires_clarification->toBeTrue()
        ->field->toBe($field)
        ->saved_fields->toBe(['blood_type'])
        ->and($user->profile()->firstOrFail())
        ->blood_type->toBe(BloodType::OPositive)
        ->height->toBe(175.0)
        ->weight->toBe(70.0)
        ->target_weight->toBe(65.0)
        ->age->toBe(30);
})->with([
    'weight above 500 kg' => [['weight' => 600], 'weight'],
    'weight above the pound limit' => [['weight' => 1200, 'weight_unit' => 'lb'], 'weight'],
    'height below 50 cm' => [['height' => 30], 'height'],
    'height above 300 cm in inches' => [['height' => 200, 'height_unit' => 'in'], 'height'],
    'target weight below 20 kg' => [['target_weight' => 10], 'target_weight'],
    'unknown weight unit' => [['weight' => 70, 'weight_unit' => 'stone'], 'weight_unit'],
    'age under 13' => [['age' => 5], 'age'],
    'age over 120' => [['age' => 300], 'age'],
    'birth date in the future' => [['date_of_birth' => '2999-01-01'], 'date_of_birth'],
    'birth date under 13 years ago' => [['date_of_birth' => now()->subYears(10)->toDateString()], 'date_of_birth'],
]);

it('ignores units when the matching value is not being updated', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 70]);
    $this->actingAs($user);

    expect(biometricsUpdate($this->tool, ['blood_type' => 'O+', 'weight_unit' => 'stone', 'height_unit' => 'ft']))
        ->success->toBeTrue()
        ->and($user->profile()->firstOrFail()->blood_type)->toBe(BloodType::OPositive);
});

it('asks whether a chat weight was meant in pounds and saves it once the unit is corrected', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 70]);
    $this->actingAs($user);

    $json = biometricsUpdate($this->tool, ['weight' => 193]);

    expect($json)
        ->requires_confirmation->toBeTrue()
        ->and($json['confirmations'][0])
        ->fields->toBe(['weight'])
        ->message->toContain('193 lb')
        ->suggestion->toEqual(['weight' => 193, 'weight_unit' => 'lb'])
        ->and($user->profile()->firstOrFail()->weight)->toBe(70.0);

    expect(biometricsUpdate($this->tool, ['weight' => 193, 'weight_unit' => 'lb']))->success->toBeTrue()
        ->and($user->profile()->firstOrFail()->weight)->toBe(87.54);
});

it('saves unusual measurements only with the confirmation token for those exact values', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 70]);
    $this->actingAs($user);

    $token = biometricsUpdate($this->tool, ['weight' => 193])['confirmation_token'];

    expect(biometricsUpdate($this->tool, ['weight' => 193, 'confirmation_token' => 'not-the-token']))->requires_confirmation->toBeTrue()
        ->and(biometricsUpdate($this->tool, ['weight' => 195, 'confirmation_token' => $token]))->requires_confirmation->toBeTrue()
        ->and($user->profile()->firstOrFail()->weight)->toBe(70.0)
        ->and(biometricsUpdate($this->tool, ['weight' => 193, 'confirmation_token' => $token]))->success->toBeTrue()
        ->and($user->profile()->firstOrFail()->weight)->toBe(193.0);
});

it('saves the other fields while unusual measurements wait for confirmation', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 70, 'sex' => Sex::Male, 'goal_choice' => null]);
    $this->actingAs($user);

    $json = biometricsUpdate($this->tool, ['weight' => 193, 'sex' => 'female', 'goal_choice' => 'weight_loss']);

    expect($json)
        ->requires_confirmation->toBeTrue()
        ->saved_fields->toEqualCanonicalizing(['sex', 'goal_choice'])
        ->and($user->profile()->firstOrFail())
        ->sex->toBe(Sex::Female)
        ->goal_choice->toBe(GoalChoice::WeightLoss)
        ->weight->toBe(70.0);
});

it('suggests pounds for a saved weight when only the height changes', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 193, 'needs_re_onboarding' => true]);
    $this->actingAs($user);

    $json = biometricsUpdate($this->tool, ['height' => 176]);

    expect($json['confirmations'][0])
        ->fields->toBe(['height'])
        ->suggestion->toEqual(['weight' => 193, 'weight_unit' => 'lb'])
        ->and(biometricsUpdate($this->tool, ['height' => 176, 'weight' => 193, 'weight_unit' => 'lb']))->success->toBeTrue()
        ->and($user->profile()->firstOrFail())
        ->height->toBe(176.0)
        ->weight->toBe(87.54)
        ->needs_re_onboarding->toBeFalse();
});

it('suggests inches when a height only makes sense in inches', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 80]);
    $this->actingAs($user);

    $json = biometricsUpdate($this->tool, ['height' => 70]);

    expect($json['confirmations'][0])
        ->message->toContain('70 in (5 ft 10 in)')
        ->suggestion->toEqual(['height' => 70, 'height_unit' => 'in'])
        ->and($user->profile()->firstOrFail()->height)->toBe(175.0);
});

it('asks a plain double-check when no unit mix-up explains the measurements', function (array $arguments): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 70]);
    $this->actingAs($user);

    $json = biometricsUpdate($this->tool, $arguments);

    expect($json['confirmations'][0])
        ->message->toStartWith('Just checking: is a height of')
        ->suggestion->toBeNull()
        ->and($user->profile()->firstOrFail()->weight)->toBe(70.0);
})->with([
    'weight too low for the height' => [['weight' => 30]],
    'pounds already stated' => [['weight' => 450, 'weight_unit' => 'lb', 'height' => 152]],
]);

it('asks about a target weight that only makes sense in pounds', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 70, 'target_weight' => 65]);
    $this->actingAs($user);

    $json = biometricsUpdate($this->tool, ['target_weight' => 180]);

    expect($json['confirmations'][0])
        ->fields->toBe(['target_weight'])
        ->suggestion->toEqual(['target_weight' => 180, 'target_weight_unit' => 'lb'])
        ->and($user->profile()->firstOrFail()->target_weight)->toBe(65.0);
});

it('does not ask again when confirmed measurements change only a little', function (bool $needsReview, bool $asks): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 170, 'weight' => 135, 'needs_re_onboarding' => $needsReview]);
    $this->actingAs($user);

    expect(array_key_exists('requires_confirmation', biometricsUpdate($this->tool, ['weight' => 133])))->toBe($asks);
})->with([
    'confirmed earlier' => [false, false],
    'still waiting for review' => [true, true],
]);

it('clears the review request when height or weight is saved through chat', function (array $arguments, bool $needsReview): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'height' => 175,
        'weight' => 193,
        'needs_re_onboarding' => true,
    ]);
    $this->actingAs($user);

    expect(biometricsUpdate($this->tool, $arguments))->success->toBeTrue()
        ->and($user->profile()->firstOrFail()->needs_re_onboarding)->toBe($needsReview);
})->with([
    'corrected weight' => [['weight' => 87.54], false],
    'unrelated update' => [['blood_type' => 'O+'], true],
]);

it('clears the review request once the user confirms the flagged values', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 193, 'needs_re_onboarding' => true]);
    $this->actingAs($user);

    $token = biometricsUpdate($this->tool, ['weight' => 193])['confirmation_token'];

    expect(biometricsUpdate($this->tool, ['weight' => 193, 'confirmation_token' => $token]))->success->toBeTrue()
        ->and($user->profile()->firstOrFail()->needs_re_onboarding)->toBeFalse();
});
