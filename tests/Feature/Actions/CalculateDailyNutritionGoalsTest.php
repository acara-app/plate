<?php

declare(strict_types=1);

use App\Actions\CalculateDailyNutritionGoals;
use App\Data\DailyNutritionGoalsData;
use App\Enums\AnimalProductChoice;
use App\Enums\GoalChoice;
use App\Enums\IntensityChoice;
use App\Enums\Sex;
use App\Models\UserProfile;

covers(CalculateDailyNutritionGoals::class, DailyNutritionGoalsData::class);

function goalsProfile(array $attributes = []): UserProfile
{
    return UserProfile::factory()->create([
        'age' => 30,
        'date_of_birth' => null,
        'height' => 180,
        'weight' => 80,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::HealthyEating,
        'animal_product_choice' => AnimalProductChoice::Omnivore,
        'intensity_choice' => IntensityChoice::Balanced,
        'derived_activity_multiplier' => 1.5,
        ...$attributes,
    ]);
}

it('sets the daily calorie goal from energy needs and the chosen goal', function (GoalChoice $goal, int $calories): void {
    $goals = resolve(CalculateDailyNutritionGoals::class)->handle(goalsProfile(['goal_choice' => $goal]));

    expect($goals?->calories)->toBe($calories);
})->with([
    'weight loss eats 500 under' => [GoalChoice::WeightLoss, 2170],
    'muscle gain eats 300 over' => [GoalChoice::BuildMuscle, 2970],
    'steady glucose eats 300 under' => [GoalChoice::Spikes, 2370],
    'heart health eats 300 under' => [GoalChoice::HeartHealth, 2370],
    'healthy eating eats 300 under' => [GoalChoice::HealthyEating, 2370],
]);

it('splits the goal into grams by the diet and adds 14 g of fiber per 1000 kcal', function (array $choices, array $grams): void {
    $goals = resolve(CalculateDailyNutritionGoals::class)->handle(goalsProfile($choices));

    expect($goals?->toArray())->toBe(['calories' => 2370, ...$grams]);
})->with([
    'balanced 50/20/30' => [[], ['protein' => 119, 'carbs' => 296, 'fat' => 79, 'fiber' => 33]],
    'low carb 20/35/45' => [['goal_choice' => GoalChoice::Spikes, 'intensity_choice' => IntensityChoice::Aggressive], ['protein' => 207, 'carbs' => 119, 'fat' => 119, 'fiber' => 33]],
]);

it('never sets a goal below 1200 kcal', function (): void {
    $goals = resolve(CalculateDailyNutritionGoals::class)->handle(goalsProfile([
        'age' => 70, 'height' => 150, 'weight' => 45, 'sex' => Sex::Female,
        'goal_choice' => GoalChoice::WeightLoss, 'derived_activity_multiplier' => 1.3,
    ]));

    expect($goals?->calories)->toBe(1200)
        ->and($goals?->fiber)->toBe(17);
});

it('has no goals until the profile has what the calculation needs', function (?array $attributes): void {
    $profile = $attributes === null ? null : goalsProfile($attributes);

    expect(resolve(CalculateDailyNutritionGoals::class)->handle($profile))->toBeNull();
})->with([
    'no profile' => [null],
    'no weight' => [['weight' => null]],
    'no goal' => [['goal_choice' => null]],
]);
