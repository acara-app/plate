<?php

declare(strict_types=1);

use App\Actions\AiAgents\CreateMealPlanPrompt;
use App\Models\DietaryPreference;
use App\Models\Goal;
use App\Models\HealthCondition;
use App\Models\Lifestyle;
use App\Models\User;
use App\Models\UserProfile;

test('it generates meal plan context for user with complete profile', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Weight Loss']);
    $lifestyle = Lifestyle::factory()->create([
        'name' => 'Active',
        'activity_level' => 'Moderate',
        'activity_multiplier' => 1.55,
    ]);

    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => 'male',
        'goal_id' => $goal->id,
        'target_weight' => 75,
        'lifestyle_id' => $lifestyle->id,
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

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)
        ->toBeString()
        ->toContain('Age')
        ->toContain('30 years')
        ->toContain('Weight Loss')
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
        'goal_id' => null,
        'lifestyle_id' => null,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)
        ->toBeString()
        ->toContain('Not specified');
});

test('it calculates correct daily calorie target for weight loss', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Weight Loss']);
    $lifestyle = Lifestyle::factory()->create(['activity_multiplier' => 1.55]);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => 'male',
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)
        ->toContain('Daily Calorie Target')
        ->toContain('calories');
});

test('it includes macronutrient ratios in output', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Muscle Gain']);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'goal_id' => $goal->id,
        'age' => 25,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)
        ->toContain('Macronutrient Targets')
        ->toContain('Protein')
        ->toContain('Carbohydrates')
        ->toContain('Fat')
        ->toContain('%');
});

test('it calculates calorie target for weight gain goal', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Weight Gain']);
    $lifestyle = Lifestyle::factory()->create(['activity_multiplier' => 1.55]);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => 'male',
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)->toContain('Daily Calorie Target');
});

test('it calculates calorie target for gain weight goal', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Gain Weight']);
    $lifestyle = Lifestyle::factory()->create(['activity_multiplier' => 1.55]);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => 'male',
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)->toContain('Daily Calorie Target');
});

test('it calculates calorie target for muscle gain goal', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Muscle Gain']);
    $lifestyle = Lifestyle::factory()->create(['activity_multiplier' => 1.55]);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => 'male',
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)->toContain('Daily Calorie Target');
});

test('it calculates calorie target for maintain weight goal', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Maintain Weight']);
    $lifestyle = Lifestyle::factory()->create(['activity_multiplier' => 1.55]);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => 'male',
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)->toContain('Daily Calorie Target');
});

test('it calculates calorie target for maintenance goal', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Maintenance']);
    $lifestyle = Lifestyle::factory()->create(['activity_multiplier' => 1.55]);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => 'male',
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)->toContain('Daily Calorie Target');
});

test('it calculates calorie target for lose weight goal', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Lose Weight']);
    $lifestyle = Lifestyle::factory()->create(['activity_multiplier' => 1.55]);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => 'male',
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)->toContain('Daily Calorie Target');
});

test('it calculates calorie target for unknown goal using default', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Some Custom Goal']);
    $lifestyle = Lifestyle::factory()->create(['activity_multiplier' => 1.55]);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => 'male',
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)->toContain('Daily Calorie Target');
});

test('it returns null calorie target when tdee cannot be calculated', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Weight Loss']);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'goal_id' => $goal->id,
        'age' => null,
        'height' => null,
        'weight' => null,
        'sex' => null,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)
        ->toBeString();
});

test('it uses default macronutrient ratios when no goal is set', function (): void {
    $user = User::factory()->create();

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'goal_id' => null,
        'age' => 25,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)
        ->toContain('Macronutrient Targets')
        ->toContain('30%')
        ->toContain('40%');
});

test('it calculates macronutrient ratios for weight loss', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Weight Loss']);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'goal_id' => $goal->id,
        'age' => 25,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)
        ->toContain('35%')
        ->toContain('30%');
});

test('it calculates macronutrient ratios for lose weight', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Lose Weight']);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'goal_id' => $goal->id,
        'age' => 25,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)
        ->toContain('35%')
        ->toContain('30%');
});

test('it calculates macronutrient ratios for gain weight', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Gain Weight']);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'goal_id' => $goal->id,
        'age' => 25,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)
        ->toContain('30%')
        ->toContain('45%')
        ->toContain('25%');
});

test('it calculates macronutrient ratios for maintain weight', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Maintain Weight']);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'goal_id' => $goal->id,
        'age' => 25,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)
        ->toContain('30%')
        ->toContain('40%');
});

test('it calculates macronutrient ratios for maintenance', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Maintenance']);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'goal_id' => $goal->id,
        'age' => 25,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)
        ->toContain('30%')
        ->toContain('40%');
});

test('it calculates macronutrient ratios for unknown goal using default', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Some Unknown Goal']);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'goal_id' => $goal->id,
        'age' => 25,
    ]);

    $action = new CreateMealPlanPrompt();
    $result = $action->handle($user);

    expect($result)
        ->toContain('30%')
        ->toContain('40%');
});

test('it throws exception when user has no profile', function (): void {
    $user = User::factory()->create();

    $action = new CreateMealPlanPrompt();
    $action->handle($user);
})->throws(RuntimeException::class, 'User profile is required to create a meal plan.');
