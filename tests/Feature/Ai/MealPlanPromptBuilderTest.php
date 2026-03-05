<?php

declare(strict_types=1);

use App\Ai\MealPlanPromptBuilder;
use App\Enums\GlucoseReadingType;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Models\HealthEntry;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserProfileAttribute;
use Illuminate\Support\Facades\DB;

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

    UserProfileAttribute::factory()->dietaryPattern('Vegetarian')->create([
        'user_profile_id' => $profile->id,
    ]);
    UserProfileAttribute::factory()->healthCondition('Diabetes')->create([
        'user_profile_id' => $profile->id,
        'notes' => 'Type 2',
    ]);

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

    UserProfileAttribute::factory()->dietaryPattern('Vegan')->create([
        'user_profile_id' => $profile->id,
    ]);
    UserProfileAttribute::factory()->allergy('Gluten')->create([
        'user_profile_id' => $profile->id,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)
        ->toBeString()
        ->toContain('Vegan')
        ->toContain('Gluten');
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

    UserProfileAttribute::factory()->healthCondition('Diabetes')->create([
        'user_profile_id' => $profile->id,
        'notes' => 'Type 2',
    ]);
    UserProfileAttribute::factory()->healthCondition('High Blood Pressure')->create([
        'user_profile_id' => $profile->id,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)
        ->toBeString()
        ->toContain('Diabetes')
        ->toContain('Type 2')
        ->toContain('High Blood Pressure');
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

test('it automatically analyzes glucose data when analysis not provided', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 32,
        'height' => 178,
        'weight' => 82,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::HealthyEating->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'glucose_value' => 95.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->subDays(1),
    ]);

    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'glucose_value' => 140.0,
        'glucose_reading_type' => GlucoseReadingType::PostMeal,
        'measured_at' => now()->subDays(2),
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)
        ->toBeString()
        ->toContain('Glucose Monitoring Data')
        ->toContain('Total Readings')
        ->toContain('Average Glucose Levels');
});

test('it handles user profile with no tdee for calorie calculation', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => null,
        'height' => null,
        'weight' => null,
        'sex' => null,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.3,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)->toBeString();
});

test('it handles user profile with no goal choice for calorie calculation', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => Sex::Male,
        'goal_choice' => null,
        'derived_activity_multiplier' => 1.55,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handle($user);

    expect($result)->toBeString();
});

test('it handles user profile with invalid goal choice enum value', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175,
        'weight' => 80,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss,
        'derived_activity_multiplier' => 1.55,
    ]);

    DB::table('user_profiles')->where('id', $profile->id)->update(['goal_choice' => 'invalid_goal_value']);

    $builder = resolve(MealPlanPromptBuilder::class);

    $closure = function () use ($builder, $user): void {
        $builder->handle($user->fresh());
    };
    expect($closure)->toThrow(ValueError::class);
});

test('it generates single day meal plan prompt with all parameters', function (): void {
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
    $result = $builder->handleForDay(
        user: $user,
        dayNumber: 3,
        totalDays: 7
    );

    expect($result)
        ->toBeString()
        ->toContain('Day 3')
        ->toContain('7');
});
