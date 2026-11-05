<?php

declare(strict_types=1);

use App\Actions\CalculateDailyNutritionGoals;
use App\Enums\AllergySeverity;
use App\Enums\AnimalProductChoice;
use App\Enums\BloodType;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Enums\IntensityChoice;
use App\Enums\MeasurementSystem;
use App\Enums\Sex;
use App\Enums\UserProfileAttributeCategory;
use App\Http\Controllers\OnboardingController;
use App\Models\User;
use Illuminate\Support\Facades\Date;

covers(OnboardingController::class);

it('renders biometrics page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('onboarding.biometrics.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/biometrics')
            ->has('profile')
            ->where('currentAge', null)
            ->has('sexOptions')
            ->has('bloodTypeOptions'));
});

it('pre-fills the age from a stored date of birth', function (): void {
    Date::setTestNow('2026-04-04 12:00:00');

    $user = User::factory()->create();
    $user->profile()->create([
        'age' => 25,
        'date_of_birth' => '1996-04-04',
    ]);

    $this->actingAs($user)
        ->get(route('onboarding.biometrics.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/biometrics')
            ->where('currentAge', 30));

    Date::setTestNow();
});

it('may store biometrics data with blood_type', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 175,
            'weight' => 70,
            'sex' => Sex::Male->value,
            'blood_type' => BloodType::APositive->value,
        ]);

    $response->assertRedirectToRoute('onboarding.identity.show');

    $profile = $user->profile()->first();

    expect($profile)->not->toBeNull()
        ->age->toBe(30)
        ->height->toBe(175.0)
        ->weight->toBe(70.0)
        ->sex->toBe(Sex::Male)
        ->blood_type->toBe(BloodType::APositive);
});

it('may store biometrics without date_of_birth and blood_type', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 175,
            'weight' => 70,
            'sex' => Sex::Male->value,
        ]);

    $response->assertRedirectToRoute('onboarding.identity.show');

    $profile = $user->profile()->first();

    expect($profile)->not->toBeNull()
        ->age->toBe(30)
        ->date_of_birth->toBeNull()
        ->blood_type->toBeNull();
});

it('may clear biometrics blood_type by submitting empty string', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([
        'age' => 30,
        'height' => 175,
        'weight' => 70,
        'sex' => Sex::Male->value,
        'blood_type' => BloodType::APositive->value,
    ]);

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 175,
            'weight' => 70,
            'sex' => Sex::Male->value,
            'blood_type' => '',
        ]);

    $response->assertRedirectToRoute('onboarding.identity.show');

    expect($user->profile()->first())->blood_type->toBeNull();
});

it('requires age for biometrics', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'height' => 175,
            'weight' => 70,
            'sex' => Sex::Male->value,
        ]);

    $response->assertSessionHasErrors('age');
});

it('requires age to be at least 13', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 12,
            'height' => 175,
            'weight' => 70,
            'sex' => Sex::Male->value,
        ]);

    $response->assertSessionHasErrors('age');
});

it('requires age to be at most 120', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 121,
            'height' => 175,
            'weight' => 70,
            'sex' => Sex::Male->value,
        ]);

    $response->assertSessionHasErrors('age');
});

it('keeps a stored date of birth when the entered age still matches it', function (): void {
    Date::setTestNow('2026-04-04 12:00:00');

    $user = User::factory()->create();
    $user->profile()->create([
        'age' => 30,
        'date_of_birth' => '1996-04-04',
    ]);

    $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 175,
            'weight' => 70,
            'sex' => Sex::Male->value,
        ])
        ->assertSessionHasNoErrors();

    expect($user->profile()->first())
        ->age->toBe(30)
        ->date_of_birth->format('Y-m-d')->toBe('1996-04-04');

    Date::setTestNow();
});

it('uses the entered age instead of a stored date of birth that contradicts it', function (): void {
    Date::setTestNow('2026-04-04 12:00:00');

    $user = User::factory()->create();
    $user->profile()->create([
        'age' => 30,
        'date_of_birth' => '1996-04-04',
    ]);

    $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 31,
            'height' => 175,
            'weight' => 70,
            'sex' => Sex::Male->value,
        ])
        ->assertSessionHasNoErrors();

    $profile = $user->profile()->first();

    expect($profile)
        ->age->toBe(31)
        ->date_of_birth->toBeNull()
        ->and($profile->currentAge())->toBe(31)
        ->and($profile->bmr)->toBe(1643.75);

    Date::setTestNow();
});

it('requires height for biometrics', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'weight' => 70,
            'sex' => Sex::Male->value,
        ]);

    $response->assertSessionHasErrors('height');
});

it('requires height to be at least 50', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 49,
            'weight' => 70,
            'sex' => Sex::Male->value,
        ]);

    $response->assertSessionHasErrors('height');
});

it('requires height to be at most 300', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 301,
            'weight' => 70,
            'sex' => Sex::Male->value,
        ]);

    $response->assertSessionHasErrors('height');
});

it('requires weight for biometrics', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 175,
            'sex' => Sex::Male->value,
        ]);

    $response->assertSessionHasErrors('weight');
});

it('requires weight to be at least 20', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 175,
            'weight' => 19,
            'sex' => Sex::Male->value,
        ]);

    $response->assertSessionHasErrors('weight');
});

it('requires weight to be at most 500', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 175,
            'weight' => 501,
            'sex' => Sex::Male->value,
        ]);

    $response->assertSessionHasErrors('weight');
});

it('requires sex for biometrics', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 175,
            'weight' => 70,
        ]);

    $response->assertSessionHasErrors('sex');
});

it('requires valid sex value', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 175,
            'weight' => 70,
            'sex' => 'invalid',
        ]);

    $response->assertSessionHasErrors('sex');
});

it('renders identity page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('onboarding.identity.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/identity')
            ->has('profile'));
});

it('identity page displays existing profile choices', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([
        'goal_choice' => GoalChoice::Spikes->value,
        'animal_product_choice' => AnimalProductChoice::Omnivore->value,
        'intensity_choice' => IntensityChoice::Balanced->value,
        'age' => 30,
        'height' => 175,
        'weight' => 70,
        'sex' => Sex::Male->value,
    ]);

    $response = $this->actingAs($user)
        ->get(route('onboarding.identity.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/identity')
            ->has('profile')
            ->where('profile.goal_choice', GoalChoice::Spikes->value)
            ->where('profile.animal_product_choice', AnimalProductChoice::Omnivore->value)
            ->where('profile.intensity_choice', IntensityChoice::Balanced->value));
});

it('may store identity data and redirect to dietary preferences', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([
        'age' => 30,
        'height' => 175,
        'weight' => 70,
        'sex' => Sex::Male->value,
    ]);

    $response = $this->actingAs($user)
        ->post(route('onboarding.identity.store'), [
            'goal_choice' => GoalChoice::Spikes->value,
            'animal_product_choice' => AnimalProductChoice::Omnivore->value,
            'intensity_choice' => IntensityChoice::Balanced->value,
        ]);

    $response->assertRedirectToRoute('onboarding.dietary.show');

    $profile = $user->profile()->first();

    expect($profile)->not->toBeNull()
        ->goal_choice->toBe(GoalChoice::Spikes)
        ->animal_product_choice->toBe(AnimalProductChoice::Omnivore)
        ->intensity_choice->toBe(IntensityChoice::Balanced)
        ->calculated_diet_type->toBe(DietType::Mediterranean)
        ->derived_activity_multiplier->toBe(1.3);
});

it('requires goal_choice', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([]);

    $response = $this->actingAs($user)
        ->post(route('onboarding.identity.store'), [
            'animal_product_choice' => 'omnivore',
            'intensity_choice' => 'balanced',
        ]);

    $response->assertSessionHasErrors('goal_choice');
});

it('requires animal_product_choice', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([]);

    $response = $this->actingAs($user)
        ->post(route('onboarding.identity.store'), [
            'goal_choice' => 'spikes',
            'intensity_choice' => 'balanced',
        ]);

    $response->assertSessionHasErrors('animal_product_choice');
});

it('requires intensity_choice', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([]);

    $response = $this->actingAs($user)
        ->post(route('onboarding.identity.store'), [
            'goal_choice' => 'spikes',
            'animal_product_choice' => 'omnivore',
        ]);

    $response->assertSessionHasErrors('intensity_choice');
});

it('renders dietary preferences page without a profile', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('onboarding.dietary.show'))
        ->assertOk();
});

it('renders dietary preferences page', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([]);

    $response = $this->actingAs($user)
        ->get(route('onboarding.dietary.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/dietary-preferences')
            ->has('categories')
            ->where('categories.2.value', UserProfileAttributeCategory::DietaryPattern->value)
            ->has('severityOptions')
        );
});

it('may store dietary preferences with allergies and complete onboarding', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([]);

    $response = $this->actingAs($user)
        ->post(route('onboarding.dietary.store'), [
            'attributes' => [
                [
                    'category' => UserProfileAttributeCategory::Allergy->value,
                    'value' => 'Peanuts',
                    'severity' => AllergySeverity::Severe->value,
                    'notes' => 'Anaphylactic reaction',
                ],
                [
                    'category' => UserProfileAttributeCategory::Intolerance->value,
                    'value' => 'Lactose',
                    'severity' => null,
                    'notes' => null,
                ],
            ],
        ]);

    $response->assertRedirectToRoute('dashboard');

    $profile = $user->profile()->first();

    expect($profile)
        ->onboarding_completed->toBeTrue()
        ->onboarding_completed_at->not->toBeNull();

    expect($profile->attributes)->toHaveCount(2)
        ->and($profile->attributes->first()->value)->toBe('Peanuts')
        ->and($profile->attributes->first()->severity)->toBe(AllergySeverity::Severe);
});

it('deduplicates repeated dietary preferences when completing onboarding', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([]);

    $response = $this->actingAs($user)
        ->post(route('onboarding.dietary.store'), [
            'attributes' => [
                [
                    'category' => UserProfileAttributeCategory::Allergy->value,
                    'value' => 'Vegetarian',
                    'severity' => AllergySeverity::Mild->value,
                    'notes' => 'User follows a vegetarian diet.',
                ],
                [
                    'category' => UserProfileAttributeCategory::Allergy->value,
                    'value' => 'Vegetarian',
                    'severity' => AllergySeverity::Mild->value,
                    'notes' => 'User follows a vegetarian diet.',
                ],
            ],
        ]);

    $response->assertRedirectToRoute('dashboard');

    $profile = $user->profile()->first();
    $attribute = $profile->attributes()->sole();

    expect($profile)
        ->onboarding_completed->toBeTrue()
        ->attributes->toHaveCount(1);

    expect($attribute->value)->toBe('Vegetarian')
        ->and($attribute->notes)->toBe('User follows a vegetarian diet.');
});

it('may skip dietary preferences and complete onboarding', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([]);

    $response = $this->actingAs($user)
        ->post(route('onboarding.dietary.store'), [
            'attributes' => [],
        ]);

    $response->assertRedirectToRoute('dashboard');

    $profile = $user->profile()->first();

    expect($profile)
        ->onboarding_completed->toBeTrue()
        ->onboarding_completed_at->not->toBeNull();

    expect($profile->attributes)->toHaveCount(0);
});

it('renders completion page', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([
        'onboarding_completed' => true,
        'onboarding_completed_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('onboarding.completion.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('onboarding/completion'));
});

it('redirects to biometrics if completion page accessed without completing onboarding', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('onboarding.completion.show'));

    $response->assertRedirectToRoute('onboarding.biometrics.show');
});

it('stores imperial measurements as kilograms and centimeters', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'measurement_system' => MeasurementSystem::Imperial->value,
            'age' => 30,
            'height_feet' => 5,
            'height_inches' => 10,
            'weight' => 200,
            'sex' => Sex::Male->value,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('onboarding.identity.show');

    expect($user->profile()->first())
        ->weight->toBe(90.72)
        ->height->toBe(177.8);
});

it('treats missing inches as zero', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'measurement_system' => MeasurementSystem::Imperial->value,
            'age' => 30,
            'height_feet' => 6,
            'height_inches' => '',
            'weight' => 180,
            'sex' => Sex::Male->value,
        ])
        ->assertSessionHasNoErrors();

    expect($user->profile()->first()->height)->toBe(182.88);
});

it('gives imperial entries the same calorie and macro targets as the equivalent metric profile', function (): void {
    $user = User::factory()->create();
    $identity = [
        'goal_choice' => GoalChoice::WeightLoss,
        'animal_product_choice' => AnimalProductChoice::Omnivore,
        'intensity_choice' => IntensityChoice::Balanced,
        'derived_activity_multiplier' => 1.375,
    ];

    $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'measurement_system' => MeasurementSystem::Imperial->value,
            'age' => 40,
            'height_feet' => 5,
            'height_inches' => 10,
            'weight' => 200,
            'sex' => Sex::Female->value,
        ])
        ->assertSessionHasNoErrors();

    $imperialProfile = tap($user->profile()->firstOrFail())->update($identity)->refresh();
    $metricProfile = User::factory()->create()->profile()->create([
        'age' => 40,
        'height' => 177.8,
        'weight' => 90.72,
        'sex' => Sex::Female,
        ...$identity,
    ])->refresh();
    $calculateGoals = resolve(CalculateDailyNutritionGoals::class);

    expect($imperialProfile->weight)->toEqualWithDelta(90.7, 0.05)
        ->and($imperialProfile->tdee)->toBe($metricProfile->tdee)
        ->and($calculateGoals->handle($imperialProfile))->not->toBeNull()
        ->toEqual($calculateGoals->handle($metricProfile));
});

it('validates imperial measurements', function (array $overrides, string $errorKey): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'measurement_system' => MeasurementSystem::Imperial->value,
            'age' => 30,
            'height_feet' => 5,
            'height_inches' => 10,
            'weight' => 180,
            'sex' => Sex::Male->value,
            ...$overrides,
        ])
        ->assertSessionHasErrors($errorKey);

    expect($user->profile()->exists())->toBeFalse();
})->with([
    'missing feet' => [['height_feet' => null], 'height_feet'],
    'feet below the range' => [['height_feet' => 1], 'height_feet'],
    'feet above the range' => [['height_feet' => 9], 'height_feet'],
    'twelve inches' => [['height_inches' => 12], 'height_inches'],
    'negative inches' => [['height_inches' => -1], 'height_inches'],
    'weight below the range' => [['weight' => 44], 'weight'],
    'weight above the range' => [['weight' => 1101], 'weight'],
]);

it('rejects an unknown measurement system', function (mixed $measurementSystem): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'measurement_system' => $measurementSystem,
            'age' => 30,
            'height' => 175,
            'weight' => 70,
            'sex' => Sex::Male->value,
        ])
        ->assertSessionHasErrors('measurement_system');
})->with([
    'unknown value' => ['stones'],
    'array value' => [[MeasurementSystem::Imperial->value]],
]);

it('asks whether a kilogram entry was meant as pounds before saving', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 175,
            'weight' => 193,
            'sex' => Sex::Male->value,
        ])
        ->assertSessionHasErrors(['weight_in_pounds' => 'Did you mean 193 lb?'])
        ->assertSessionDoesntHaveErrors('measurements');

    expect($user->profile()->exists())->toBeFalse();
});

it('asks to double-check measurements that pounds would not explain', function (array $payload): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'sex' => Sex::Male->value,
            ...$payload,
        ])
        ->assertSessionHasErrors(['measurements' => 'Please double-check your height and weight.'])
        ->assertSessionDoesntHaveErrors('weight_in_pounds');

    expect($user->profile()->exists())->toBeFalse();
})->with([
    'height typed in inches' => [['height' => 70, 'weight' => 80]],
    'kilograms typed into the pounds field' => [[
        'measurement_system' => MeasurementSystem::Imperial->value,
        'height_feet' => 5,
        'height_inches' => 10,
        'weight' => 70,
    ]],
    'very high imperial entry' => [[
        'measurement_system' => MeasurementSystem::Imperial->value,
        'height_feet' => 5,
        'height_inches' => 0,
        'weight' => 450,
    ]],
]);

it('saves the original measurements once the person confirms them', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 175,
            'weight' => 193,
            'sex' => Sex::Male->value,
            'confirm_measurements' => true,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('onboarding.identity.show');

    expect($user->profile()->first()->weight)->toBe(193.0);
});

it('only checks the measurements once the rest of the form is valid', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 175,
            'weight' => 193,
        ])
        ->assertSessionHasErrors('sex')
        ->assertSessionDoesntHaveErrors(['weight_in_pounds', 'measurements']);
});

it('clears the review request and returns onboarded people to the dashboard', function (array $payload, float $weight): void {
    $user = User::factory()->create();
    $user->profile()->create([
        'age' => 30,
        'height' => 175,
        'weight' => 193,
        'sex' => Sex::Male->value,
        'needs_re_onboarding' => true,
        'onboarding_completed' => true,
        'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'sex' => Sex::Male->value,
            ...$payload,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('dashboard')
        ->assertInertiaFlash('toast', [
            'message' => __('common.onboarding.biometrics.review_confirmed_toast'),
            'type' => 'success',
        ]);

    expect($user->profile()->first())
        ->needs_re_onboarding->toBeFalse()
        ->weight->toBe($weight);
})->with([
    'corrected in kilograms' => [['height' => 175, 'weight' => 87.54], 87.54],
    'switched to pounds' => [[
        'measurement_system' => MeasurementSystem::Imperial->value,
        'height_feet' => 5,
        'height_inches' => 9,
        'weight' => 193,
    ], 87.54],
    'confirmed as entered' => [['height' => 175, 'weight' => 193, 'confirm_measurements' => true], 193.0],
]);

it('clears the review request and continues onboarding for people who have not finished it', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([
        'age' => 30,
        'height' => 175,
        'weight' => 193,
        'sex' => Sex::Male->value,
        'needs_re_onboarding' => true,
    ]);

    $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'height' => 175,
            'weight' => 87.54,
            'sex' => Sex::Male->value,
        ])
        ->assertRedirectToRoute('onboarding.identity.show');

    expect($user->profile()->first()->needs_re_onboarding)->toBeFalse();
});
