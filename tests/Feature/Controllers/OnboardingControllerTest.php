<?php

declare(strict_types=1);

use App\Enums\Sex;
use App\Models\User;

// Questionnaire Tests
it('renders questionnaire page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('onboarding.questionnaire.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('onboarding/questionnaire'));
});

it('allows users to view questionnaire even if onboarding already completed', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([
        'onboarding_completed' => true,
        'onboarding_completed_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('onboarding.questionnaire.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('onboarding/questionnaire'));
});

// Biometrics Tests
it('renders biometrics page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('onboarding.biometrics.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/biometrics')
            ->has('profile')
            ->has('sexOptions'));
});

it('may store biometrics data', function (): void {
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
        ->height->toBe(175.0)
        ->weight->toBe(70.0)
        ->sex->toBe(Sex::Male);
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

// Identity Tests
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
        'goal_choice' => App\Enums\GoalChoice::Spikes->value,
        'animal_product_choice' => App\Enums\AnimalProductChoice::Omnivore->value,
        'intensity_choice' => App\Enums\IntensityChoice::Balanced->value,
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
            ->where('profile.goal_choice', App\Enums\GoalChoice::Spikes->value)
            ->where('profile.animal_product_choice', App\Enums\AnimalProductChoice::Omnivore->value)
            ->where('profile.intensity_choice', App\Enums\IntensityChoice::Balanced->value));
});

it('may store identity data and complete onboarding', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([
        'age' => 30,
        'height' => 175,
        'weight' => 70,
        'sex' => Sex::Male->value,
    ]);

    $response = $this->actingAs($user)
        ->post(route('onboarding.identity.store'), [
            'goal_choice' => App\Enums\GoalChoice::Spikes->value,
            'animal_product_choice' => App\Enums\AnimalProductChoice::Omnivore->value,
            'intensity_choice' => App\Enums\IntensityChoice::Balanced->value,
        ]);

    $response->assertRedirectToRoute('meal-plans.create');

    $profile = $user->profile()->first();

    expect($profile)->not->toBeNull()
        ->goal_choice->toBe(App\Enums\GoalChoice::Spikes->value)
        ->animal_product_choice->toBe(App\Enums\AnimalProductChoice::Omnivore->value)
        ->intensity_choice->toBe(App\Enums\IntensityChoice::Balanced->value)
        ->calculated_diet_type->toBe(App\Enums\DietType::Mediterranean)
        ->derived_activity_multiplier->toBe(1.3)
        ->onboarding_completed->toBeTrue()
        ->onboarding_completed_at->not->toBeNull();
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

// Completion Tests
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

it('redirects to questionnaire if completion page accessed without completing onboarding', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('onboarding.completion.show'));

    $response->assertRedirectToRoute('onboarding.questionnaire.show');
});
