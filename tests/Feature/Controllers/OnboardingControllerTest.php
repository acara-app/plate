<?php

declare(strict_types=1);

use App\Enums\Sex;
use App\Models\DietaryPreference;
use App\Models\Goal;
use App\Models\HealthCondition;
use App\Models\Lifestyle;
use App\Models\User;
use Workflow\WorkflowStub;

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

    $response->assertRedirectToRoute('onboarding.goals.show');

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

// Goals Tests
it('renders goals page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('onboarding.goals.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/goals')
            ->has('profile')
            ->has('goals'));
});

it('may store goals data', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.goals.store'), [
            'goal_id' => $goal->id,
            'target_weight' => 65,
            'additional_goals' => 'Build muscle and improve endurance',
        ]);

    $response->assertRedirectToRoute('onboarding.lifestyle.show');

    $profile = $user->profile()->first();

    expect($profile)->not->toBeNull()
        ->goal_id->toBe($goal->id)
        ->target_weight->toBe(65.0)
        ->additional_goals->toBe('Build muscle and improve endurance');
});

it('allows optional goal_id', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.goals.store'), [
            'target_weight' => 65,
        ]);

    $response->assertRedirectToRoute('onboarding.lifestyle.show');
});

it('allows skipping goals step with empty request', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.goals.store'), []);

    $response->assertRedirectToRoute('onboarding.lifestyle.show');
});

it('requires valid goal_id', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.goals.store'), [
            'goal_id' => 99999,
            'target_weight' => 65,
        ]);

    $response->assertSessionHasErrors('goal_id');
});

it('allows optional target_weight', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.goals.store'), [
            'goal_id' => $goal->id,
        ]);

    $response->assertRedirectToRoute('onboarding.lifestyle.show');
});

it('requires target_weight to be at least 20 if provided', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.goals.store'), [
            'goal_id' => $goal->id,
            'target_weight' => 19,
        ]);

    $response->assertSessionHasErrors('target_weight');
});

it('requires target_weight to be at most 500 if provided', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.goals.store'), [
            'goal_id' => $goal->id,
            'target_weight' => 501,
        ]);

    $response->assertSessionHasErrors('target_weight');
});

it('allows optional additional_goals', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.goals.store'), [
            'goal_id' => $goal->id,
        ]);

    $response->assertRedirectToRoute('onboarding.lifestyle.show');
});

it('requires additional_goals to be at most 1000 characters', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.goals.store'), [
            'goal_id' => $goal->id,
            'additional_goals' => str_repeat('a', 1001),
        ]);

    $response->assertSessionHasErrors('additional_goals');
});

// Lifestyle Tests
it('renders lifestyle page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('onboarding.lifestyle.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/life-style-page')
            ->has('profile')
            ->has('lifestyles'));
});

it('may store lifestyle data', function (): void {
    $user = User::factory()->create();
    $lifestyle = Lifestyle::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.lifestyle.store'), [
            'lifestyle_id' => $lifestyle->id,
        ]);

    $response->assertRedirectToRoute('onboarding.dietary-preferences.show');

    $profile = $user->profile()->first();

    expect($profile)->not->toBeNull()
        ->lifestyle_id->toBe($lifestyle->id);
});

it('allows optional lifestyle_id', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.lifestyle.store'), []);

    $response->assertRedirectToRoute('onboarding.dietary-preferences.show');
});

it('allows skipping lifestyle step with empty request', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.lifestyle.store'), []);

    $response->assertRedirectToRoute('onboarding.dietary-preferences.show');
});

it('requires valid lifestyle_id', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.lifestyle.store'), [
            'lifestyle_id' => 99999,
        ]);

    $response->assertSessionHasErrors('lifestyle_id');
});

// Dietary Preferences Tests
it('renders dietary preferences page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('onboarding.dietary-preferences.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/dietary-preferences')
            ->has('profile')
            ->has('selectedPreferences')
            ->has('preferences'));
});

it('renders dietary preferences page with existing preferences', function (): void {
    $user = User::factory()->create();
    $profile = $user->profile()->create([]);

    $pref1 = DietaryPreference::factory()->create(['name' => 'Peanuts', 'type' => 'allergy']);
    $pref2 = DietaryPreference::factory()->create(['name' => 'Lactose', 'type' => 'intolerance']);

    $profile->dietaryPreferences()->attach($pref1->id, [
        'severity' => 'severe',
        'notes' => 'EpiPen required',
    ]);
    $profile->dietaryPreferences()->attach($pref2->id, [
        'severity' => 'moderate',
        'notes' => 'Causes discomfort',
    ]);

    $response = $this->actingAs($user)
        ->get(route('onboarding.dietary-preferences.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/dietary-preferences')
            ->has('profile')
            ->has('selectedPreferences', 2)
            ->has('selectedPreferencesData')
            ->where('selectedPreferencesData.'.$pref1->id.'.severity', 'severe')
            ->where('selectedPreferencesData.'.$pref1->id.'.notes', 'EpiPen required')
            ->where('selectedPreferencesData.'.$pref2->id.'.severity', 'moderate')
            ->where('selectedPreferencesData.'.$pref2->id.'.notes', 'Causes discomfort')
            ->has('preferences'));
});

it('may store dietary preferences', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([]);

    $pref1 = DietaryPreference::factory()->create(['name' => 'Vegan', 'type' => 'pattern']);
    $pref2 = DietaryPreference::factory()->create(['name' => 'Gluten Free', 'type' => 'intolerance']);

    $response = $this->actingAs($user)
        ->post(route('onboarding.dietary-preferences.store'), [
            'dietary_preference_ids' => [$pref1->id, $pref2->id],
        ]);

    $response->assertRedirectToRoute('onboarding.health-conditions.show');

    $profile = $user->profile()->first();

    expect($profile->dietaryPreferences)
        ->toHaveCount(2)
        ->pluck('id')->toArray()->toBe([$pref1->id, $pref2->id]);
});

it('allows empty dietary preferences', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.dietary-preferences.store'), []);

    $response->assertRedirectToRoute('onboarding.health-conditions.show');
});

it('requires valid dietary preference ids', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.dietary-preferences.store'), [
            'dietary_preference_ids' => [99999],
        ]);

    $response->assertSessionHasErrors('dietary_preference_ids.0');
});

// Health Conditions Tests
it('renders health conditions page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('onboarding.health-conditions.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/health-conditions')
            ->has('profile')
            ->has('selectedConditions')
            ->has('healthConditions'));
});

it('renders health conditions ordered by order column', function (): void {
    $user = User::factory()->create();

    HealthCondition::factory()->create(['name' => 'Third', 'order' => 3]);
    HealthCondition::factory()->create(['name' => 'First', 'order' => 1]);
    HealthCondition::factory()->create(['name' => 'Second', 'order' => 2]);

    $response = $this->actingAs($user)
        ->get(route('onboarding.health-conditions.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/health-conditions')
            ->has('healthConditions', 3)
            ->where('healthConditions.0.name', 'First')
            ->where('healthConditions.1.name', 'Second')
            ->where('healthConditions.2.name', 'Third'));
});

it('may store health conditions', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([]);

    $condition1 = HealthCondition::factory()->create(['name' => 'Diabetes']);
    $condition2 = HealthCondition::factory()->create(['name' => 'Hypertension']);

    $response = $this->actingAs($user)
        ->post(route('onboarding.health-conditions.store'), [
            'health_condition_ids' => [$condition1->id, $condition2->id],
            'notes' => ['Managing with medication', 'Controlled with diet'],
        ]);

    $response->assertRedirectToRoute('onboarding.medications.show');

    $profile = $user->profile()->first();

    // Onboarding should NOT be completed yet
    expect($profile)->not->toBeNull()
        ->onboarding_completed->toBeFalsy();

    expect($profile->healthConditions)
        ->toHaveCount(2);

    expect($profile->healthConditions->first()->pivot->notes)
        ->toBe('Managing with medication');
});

it('allows empty health conditions', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.health-conditions.store'), []);

    $response->assertRedirectToRoute('onboarding.medications.show');

    $profile = $user->profile()->first();

    // Onboarding should NOT be completed yet
    expect($profile)->not->toBeNull()
        ->onboarding_completed->toBeFalsy();
});

it('requires valid health condition ids', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.health-conditions.store'), [
            'health_condition_ids' => [99999],
        ]);

    $response->assertSessionHasErrors('health_condition_ids.0');
});

it('stores units_preference when provided', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([]);

    $response = $this->actingAs($user)
        ->post(route('onboarding.health-conditions.store'), [
            'units_preference' => 'mmol/L',
        ]);

    $response->assertRedirectToRoute('onboarding.medications.show');

    expect($user->profile->fresh()->units_preference->value)->toBe('mmol/L');
});

it('requires notes to be at most 500 characters', function (): void {
    $user = User::factory()->create();
    $condition = HealthCondition::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.health-conditions.store'), [
            'health_condition_ids' => [$condition->id],
            'notes' => [str_repeat('a', 501)],
        ]);

    $response->assertSessionHasErrors('notes.0');
});

// Medications Tests
it('renders medications page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('onboarding.medications.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('onboarding/medications'));
});

it('may store medications', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([]);

    $response = $this->actingAs($user)
        ->post(route('onboarding.medications.store'), [
            'medications' => [
                [
                    'name' => 'Metformin',
                    'dosage' => '500mg',
                    'frequency' => 'twice daily',
                    'purpose' => 'Diabetes management',
                ],
                [
                    'name' => 'Lisinopril',
                    'dosage' => '10mg',
                    'frequency' => 'once daily',
                    'purpose' => 'Blood pressure',
                ],
            ],
        ]);

    $response->assertRedirectToRoute('onboarding.meal-plan-duration.show');

    $profile = $user->profile()->first();

    expect($profile->medications)
        ->toHaveCount(2);

    expect($profile->medications->first())
        ->name->toBe('Metformin')
        ->dosage->toBe('500mg')
        ->frequency->toBe('twice daily')
        ->purpose->toBe('Diabetes management');
});

it('allows skipping medications (empty submission)', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([]);

    $response = $this->actingAs($user)
        ->post(route('onboarding.medications.store'), []);

    $response->assertRedirectToRoute('onboarding.meal-plan-duration.show');

    $profile = $user->profile()->first();

    expect($profile->medications)
        ->toHaveCount(0);
});

it('requires medication name when other fields are provided', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.medications.store'), [
            'medications' => [
                [
                    'dosage' => '500mg',
                ],
            ],
        ]);

    $response->assertSessionHasErrors('medications.0.name');
});

// Meal Plan Duration Tests
it('renders meal plan duration page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('onboarding.meal-plan-duration.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('onboarding/meal-plan-duration'));
});

it('may store meal plan duration and complete onboarding', function (): void {
    WorkflowStub::fake();

    $user = User::factory()->create();
    $user->profile()->create([]);

    $response = $this->actingAs($user)
        ->post(route('onboarding.meal-plan-duration.store'), [
            'meal_plan_days' => 5,
        ]);

    $response->assertRedirectToRoute('onboarding.completion.show');

    $profile = $user->profile()->first();

    expect($profile)->not->toBeNull()
        ->onboarding_completed->toBeTrue()
        ->onboarding_completed_at->not->toBeNull();

    // Meal plan was created with selected duration
    $mealPlan = $user->mealPlans()->first();
    expect($mealPlan)->not->toBeNull();
    expect($mealPlan->metadata['status'])->toBe('generating');
});

it('requires meal_plan_days', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.meal-plan-duration.store'), []);

    $response->assertSessionHasErrors('meal_plan_days');
});

it('requires meal_plan_days to be at least 1', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.meal-plan-duration.store'), [
            'meal_plan_days' => 0,
        ]);

    $response->assertSessionHasErrors('meal_plan_days');
});

it('requires meal_plan_days to be at most 7', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('onboarding.meal-plan-duration.store'), [
            'meal_plan_days' => 8,
        ]);

    $response->assertSessionHasErrors('meal_plan_days');
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
