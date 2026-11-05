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
    $this->tool = new UpdateUserBiometrics;
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('update_user_biometrics')
        ->and($this->tool->description())->toContain('biometric');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;
    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['action', 'age', 'date_of_birth', 'height', 'height_unit', 'weight', 'weight_unit', 'sex', 'blood_type', 'goal_choice', 'animal_product_choice', 'intensity_choice', 'target_weight', 'confirm_measurements', 'additional_goals']);
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

it('converts pounds and inches from chat to the stored kilograms and centimeters', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 70]);
    $this->actingAs($user);

    $json = json_decode($this->tool->handle(new Request([
        'action' => 'update',
        'height' => 70,
        'height_unit' => 'in',
        'weight' => 200,
        'weight_unit' => 'lb',
        'target_weight' => 180,
    ])), true);

    expect($json)->success->toBeTrue()
        ->and($user->profile()->firstOrFail())
        ->height->toBe(177.8)
        ->weight->toBe(90.72)
        ->target_weight->toBe(81.65);
});

it('rejects measurements outside the accepted range without saving them', function (array $arguments, string $field): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 70, 'target_weight' => 65]);
    $this->actingAs($user);

    $json = json_decode($this->tool->handle(new Request(['action' => 'update', ...$arguments])), true);

    expect($json)
        ->requires_clarification->toBeTrue()
        ->field->toBe($field)
        ->and($user->profile()->firstOrFail())
        ->height->toBe(175.0)
        ->weight->toBe(70.0)
        ->target_weight->toBe(65.0);
})->with([
    'weight above 500 kg' => [['weight' => 600], 'weight'],
    'weight above 1,100 lb' => [['weight' => 1200, 'weight_unit' => 'lb'], 'weight'],
    'height below 50 cm' => [['height' => 30], 'height'],
    'height above 300 cm in inches' => [['height' => 200, 'height_unit' => 'in'], 'height'],
    'target weight below 20 kg' => [['target_weight' => 10], 'target_weight'],
]);

it('rejects unknown units', function (array $arguments): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $json = json_decode($this->tool->handle(new Request(['action' => 'update', 'weight' => 70, ...$arguments])), true);

    expect($json)->toHaveKey('error')
        ->and($json)->not->toHaveKey('success');
})->with([
    'weight unit' => [['weight_unit' => 'stone']],
    'height unit' => [['height_unit' => 'ft']],
]);

it('asks whether a chat weight was meant in pounds before saving it', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 87.54]);
    $this->actingAs($user);

    $json = json_decode($this->tool->handle(new Request(['action' => 'update', 'weight' => 193])), true);

    expect($json)
        ->requires_confirmation->toBeTrue()
        ->suggested_weight_unit->toBe('lb')
        ->message->toContain('193 lb')
        ->and($user->profile()->firstOrFail()->weight)->toBe(87.54);

    $json = json_decode($this->tool->handle(new Request(['action' => 'update', 'weight' => 193, 'weight_unit' => 'lb'])), true);

    expect($json)->success->toBeTrue()
        ->and($user->profile()->firstOrFail()->weight)->toBe(87.54);
});

it('asks to double-check measurements that pounds would not explain', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 80]);
    $this->actingAs($user);

    $json = json_decode($this->tool->handle(new Request(['action' => 'update', 'height' => 70])), true);

    expect($json)
        ->requires_confirmation->toBeTrue()
        ->suggested_weight_unit->toBeNull()
        ->and($user->profile()->firstOrFail()->height)->toBe(175.0);
});

it('saves unusual measurements once the user confirms them', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175, 'weight' => 87.54]);
    $this->actingAs($user);

    $json = json_decode($this->tool->handle(new Request([
        'action' => 'update',
        'weight' => 193,
        'confirm_measurements' => true,
    ])), true);

    expect($json)->success->toBeTrue()
        ->and($user->profile()->firstOrFail()->weight)->toBe(193.0);
});

it('clears the review request when height or weight is saved through chat', function (array $arguments, bool $needsReview): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'height' => 175,
        'weight' => 193,
        'needs_re_onboarding' => true,
    ]);
    $this->actingAs($user);

    $json = json_decode($this->tool->handle(new Request(['action' => 'update', ...$arguments])), true);

    expect($json)->success->toBeTrue()
        ->and($user->profile()->firstOrFail()->needs_re_onboarding)->toBe($needsReview);
})->with([
    'corrected weight' => [['weight' => 87.54], false],
    'weight confirmed as it was' => [['weight' => 193, 'confirm_measurements' => true], false],
    'unrelated update' => [['blood_type' => 'O+'], true],
]);
