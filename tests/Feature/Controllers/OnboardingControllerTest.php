<?php

declare(strict_types=1);

use App\Enums\AllergySeverity;
use App\Enums\AnimalProductChoice;
use App\Enums\BloodType;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Enums\IntensityChoice;
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
            ->has('sexOptions')
            ->has('bloodTypeOptions'));
});

it('may store biometrics data with date_of_birth and blood_type', function (): void {
    Date::setTestNow('2026-04-04 12:00:00');

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'date_of_birth' => '1996-04-04',
            'height' => 175,
            'weight' => 70,
            'sex' => Sex::Male->value,
            'blood_type' => BloodType::APositive->value,
        ]);

    $response->assertRedirectToRoute('onboarding.identity.show');

    $profile = $user->profile()->first();

    expect($profile)->not->toBeNull()
        ->date_of_birth->format('Y-m-d')->toBe('1996-04-04')
        ->age->toBe(30)
        ->height->toBe(175.0)
        ->weight->toBe(70.0)
        ->sex->toBe(Sex::Male)
        ->blood_type->toBe(BloodType::APositive);

    Date::setTestNow();
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

it('rejects future date_of_birth', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 30,
            'date_of_birth' => now()->addDay()->format('Y-m-d'),
            'height' => 175,
            'weight' => 70,
            'sex' => Sex::Male->value,
        ]);

    $response->assertSessionHasErrors('date_of_birth');
});

it('overrides age from date_of_birth when both provided', function (): void {
    Date::setTestNow('2026-04-04 12:00:00');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('onboarding.biometrics.store'), [
            'age' => 99,
            'date_of_birth' => '1996-04-04',
            'height' => 175,
            'weight' => 70,
            'sex' => Sex::Male->value,
        ]);

    expect($user->profile()->first()->age)->toBe(30);

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
