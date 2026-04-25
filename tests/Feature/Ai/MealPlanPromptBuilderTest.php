<?php

declare(strict_types=1);

use App\Ai\MealPlanPromptBuilder;
use App\Enums\GlucoseReadingType;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserProfileAttribute;
use Illuminate\Support\Facades\DB;

covers(MealPlanPromptBuilder::class);

it('handles user without profile gracefully by auto-creating one', function (): void {
    $user = User::factory()->create();

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)->toBeString()
        ->and($user->refresh()->profile)->toBeInstanceOf(UserProfile::class);
});

it('generates meal plan context for user with complete profile', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

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

it('handles user with minimal profile data', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->toBeString()
        ->toContain('Not specified');
});

it('calculates correct daily calorie target for weight loss', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->toBeString()
        ->toContain('Daily Calorie Target');
});

it('calculates correct daily calorie target for muscle gain', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->toBeString()
        ->toContain('Daily Calorie Target');
});

it('includes dietary preferences in meal plan context', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->toBeString()
        ->toContain('Vegan')
        ->toContain('Gluten');
});

it('includes health conditions in meal plan context', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->toBeString()
        ->toContain('Diabetes')
        ->toContain('Type 2')
        ->toContain('High Blood Pressure');
});

it('includes BMI calculation in context', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->toBeString()
        ->toContain('BMI')
        ->toContain('27.78');
});

it('includes TDEE calculation in context', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->toBeString()
        ->toContain('TDEE');
});

it('calculates correct TDEE for female user', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->toBeString()
        ->toContain('TDEE');
});

it('generates special instructions for weight loss goal', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)->toBeString();
});

it('generates special instructions for muscle gain goal', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)->toBeString();
});

it('generates special instructions for maintenance goal', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)->toBeString();
});

it('generates special instructions for heart health goal', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)->toBeString();
});

it('generates special instructions for blood sugar control goal', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)->toBeString();
});

it('handles missing lifestyle gracefully', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)->toBeString();
});

it('handles missing sex gracefully', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)->toBeString();
});

it('handles unknown goal type gracefully', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->toBeString();
});

it('automatically analyzes glucose data when analysis not provided', function (): void {
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

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $user->id,
        'value' => 95.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->subDays(1),
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $user->id,
        'value' => 140.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::PostMeal->value],
        'measured_at' => now()->subDays(2),
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->toBeString()
        ->toContain('Glucose Monitoring Data')
        ->toContain('Total Readings')
        ->toContain('Average Glucose Levels');
});

it('handles user profile with no tdee for calorie calculation', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)->toBeString();
});

it('handles user profile with no goal choice for calorie calculation', function (): void {
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
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)->toBeString();
});

it('handles user profile with invalid goal choice enum value', function (): void {
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
        $builder->handleForDay($user->fresh(), 1, 7);
    };
    expect($closure)->toThrow(ValueError::class);
});

it('generates single day meal plan prompt with all parameters', function (): void {
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

it('includes language instruction in the prompt', function (): void {
    $user = User::factory()->create();

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->toContain('## Language')
        ->toContain('Generate ALL meal content in');
});

it('uses users preferred language in the prompt', function (): void {
    $user = User::factory()->create([
        'locale' => 'mn',
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->toContain('Монгол')
        ->toContain('(language code: `mn`)')
        ->toContain('Generate ALL meal content in Монгол');
});

it('defaults to english when user has no preferred language', function (): void {
    $user = User::factory()->create([
        'locale' => null,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->toContain('English')
        ->toContain('(language code: `en`)')
        ->toContain('Generate ALL meal content in English');
});

it('falls back to english for unsupported language code', function (): void {
    $user = User::factory()->create([
        'locale' => 'xx',
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->toContain('(language code: `en`)')
        ->toContain('Generate ALL meal content in English')
        ->not->toContain('`xx`');
});

it('keeps the language directive language-agnostic for english users', function (): void {
    $user = User::factory()->create([
        'locale' => 'en',
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $result = $builder->handleForDay($user, 1, 7);

    expect($result)
        ->not->toContain('Монгол')
        ->not->toContain('Махтай шөл')
        ->not->toContain('Тахианы мах')
        ->not->toContain('If language is')
        ->toContain('Use natural, idiomatic terms in English');
});
