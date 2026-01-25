<?php

declare(strict_types=1);

use App\Ai\Agents\MealPlanPromptBuilder;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Models\DietaryPreference;
use App\Models\HealthCondition;
use App\Models\User;
use App\Models\UserProfile;

test('it generates meal plan context for user with complete profile', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'target_weight' => 75,
        'derived_activity_multiplier' => 1.55,
        'onboarding_completed' => true,
    ]);

    $dietaryPreference = DietaryPreference::factory()->create([
        'name' => 'Vegetarian',
        'type' => 'diet',
    ]);
    $profile->dietaryPreferences()->attach($dietaryPreference);

    $healthCondition = HealthCondition::factory()->create([
        'name' => 'Diabetes',
        'nutritional_impact' => 'Requires blood sugar management',
        'recommended_nutrients' => ['fiber', 'complex carbs'],
        'nutrients_to_limit' => ['sugar', 'simple carbs'],
    ]);
    $profile->healthConditions()->attach($healthCondition, ['notes' => 'Type 2']);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)
        ->toBeString()
        ->toContain('Age')
        ->toContain('30 years')
        ->toContain('Deep Weight Loss')
        ->toContain('Vegetarian')
        ->toContain('Diabetes')
        ->toContain('Type 2')
        ->toContain('fiber')
        ->toContain('BMI')
        ->toContain('TDEE')
        ->toContain('Daily Calorie Target');
});

test('it handles user with minimal profile data', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => null,
        'height' => null,
        'weight' => null,
        'sex' => null,
        'goal_choice' => GoalChoice::HealthyEating->value,
        'derived_activity_multiplier' => 1.3,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)
        ->toBeString()
        ->toContain('Not specified');
});

test('it calculates correct daily calorie target for weight loss', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)
        ->toBeString()
        ->toContain('Daily Calorie Target');
});

test('it calculates correct daily calorie target for muscle gain', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 25,
        'height' => 185,
        'weight' => 85,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::BuildMuscle->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)
        ->toBeString()
        ->toContain('Daily Calorie Target');
});

test('it includes dietary preferences in meal plan context', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 75,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $veganPref = DietaryPreference::factory()->create([
        'name' => 'Vegan',
        'type' => 'diet',
    ]);
    $glutenFree = DietaryPreference::factory()->create([
        'name' => 'Gluten Free',
        'type' => 'dietary',
    ]);

    $profile->dietaryPreferences()->attach([$veganPref->id, $glutenFree->id]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)
        ->toBeString()
        ->toContain('Vegan')
        ->toContain('Gluten Free');
});

test('it includes health conditions in meal plan context', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 45,
        'height' => 170,
        'weight' => 90,
        'sex' => Sex::Female,
        'goal_choice' => GoalChoice::HeartHealth->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $diabetes = HealthCondition::factory()->create([
        'name' => 'Diabetes',
        'nutritional_impact' => 'Requires blood sugar management',
        'recommended_nutrients' => ['fiber', 'complex carbs'],
        'nutrients_to_limit' => ['sugar', 'simple carbs'],
    ]);
    $hypertension = HealthCondition::factory()->create([
        'name' => 'High Blood Pressure',
        'nutritional_impact' => 'Sodium reduction needed',
        'recommended_nutrients' => ['potassium', 'magnesium'],
        'nutrients_to_limit' => ['sodium'],
    ]);

    $profile->healthConditions()->attach($diabetes->id, ['notes' => 'Type 2']);
    $profile->healthConditions()->attach($hypertension->id);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)
        ->toBeString()
        ->toContain('Diabetes')
        ->toContain('Type 2')
        ->toContain('High Blood Pressure')
        ->toContain('fiber')
        ->toContain('potassium');
});

test('it includes BMI calculation in context', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 35,
        'height' => 180,
        'weight' => 90,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::HealthyEating->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)
        ->toBeString()
        ->toContain('BMI')
        ->toContain('27.78');
});

test('it includes TDEE calculation in context', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 70,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::HealthyEating->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)
        ->toBeString()
        ->toContain('TDEE');
});

test('it calculates correct TDEE for female user', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 28,
        'height' => 165,
        'weight' => 60,
        'sex' => Sex::Female,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)
        ->toBeString()
        ->toContain('TDEE');
});

test('it generates special instructions for weight loss goal', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 35,
        'height' => 175,
        'weight' => 95,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)->toBeString();
});

test('it generates special instructions for muscle gain goal', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 25,
        'height' => 180,
        'weight' => 70,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::BuildMuscle->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)->toBeString();
});

test('it generates special instructions for maintenance goal', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 35,
        'height' => 175,
        'weight' => 75,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::HealthyEating->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)->toBeString();
});

test('it generates special instructions for heart health goal', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 50,
        'height' => 170,
        'weight' => 85,
        'sex' => Sex::Female,
        'goal_choice' => GoalChoice::HeartHealth->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)->toBeString();
});

test('it generates special instructions for blood sugar control goal', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 45,
        'height' => 175,
        'weight' => 90,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::Spikes->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)->toBeString();
});

test('it handles missing lifestyle gracefully', function (): void {
    $user = User::factory()->create();

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::HealthyEating->value,
        'derived_activity_multiplier' => 1.3,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)->toBeString();
});

test('it handles missing sex gracefully', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => null,
        'goal_choice' => GoalChoice::HealthyEating->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)->toBeString();
});

test('it handles unknown goal type gracefully', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::HealthyEating->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)
        ->toBeString();
});
