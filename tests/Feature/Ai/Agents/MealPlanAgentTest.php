<?php

declare(strict_types=1);

use App\Ai\Agents\MealPlanAgent;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Jobs\GenerateInitialMealPlanJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Prompts\AgentPrompt;

covers(MealPlanAgent::class);

it('gives the planner the guide for the selected diet', function (DietType $diet): void {
    $targets = $diet->macroTargets();

    expect(resolve(MealPlanAgent::class)->withDietType($diet)->instructions())
        ->toContain('### Planning a day of meals')
        ->toContain("Carbs {$targets['carbs']}% | Protein {$targets['protein']}% | Fat {$targets['fat']}%");
})->with(DietType::cases());

it('gives the Mediterranean planner its nutrient score card', function (): void {
    expect(resolve(MealPlanAgent::class)->withDietType(DietType::Mediterranean)->instructions())
        ->toContain('## The Mediterranean Diet Nutrient Score Card');
});

it('dispatches the initial meal plan job when handle is called', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.5,
    ]);

    $action = resolve(MealPlanAgent::class);
    $action->handle($user);

    $mealPlan = $user->mealPlans()->first();
    expect($mealPlan)->not->toBeNull()
        ->and($mealPlan->metadata['status'])->toBe('generating');

    Queue::assertPushed(
        GenerateInitialMealPlanJob::class,
        fn (GenerateInitialMealPlanJob $job): bool => $job->mealPlan->is($mealPlan),
    );
});

it('stores custom prompt in meal plan metadata when provided', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.5,
    ]);

    $action = resolve(MealPlanAgent::class);
    $action->handle($user, 7, 'No spicy food please');

    $mealPlan = $user->mealPlans()->first();
    expect($mealPlan)->not->toBeNull()
        ->and($mealPlan->metadata['custom_prompt'])->toBe('No spicy food please');
});

it('generates a DASH day with the selected diet targets even when the profile suggests another diet', function (): void {
    $user = User::factory()->create();

    $user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.5,
    ]);

    $mockResponse = [
        'meals' => [
            [
                'type' => 'breakfast',
                'name' => 'Oatmeal',
                'description' => 'Healthy breakfast',
                'preparation_instructions' => 'Cook oats',
                'ingredients' => [
                    ['name' => 'Oats', 'quantity' => '50g'],
                ],
                'portion_size' => '1 bowl',
                'calories' => 300,
                'protein_grams' => 10,
                'carbs_grams' => 50,
                'fat_grams' => 5,
                'preparation_time_minutes' => 10,
                'sort_order' => 1,
            ],
        ],
    ];

    MealPlanAgent::fake([$mockResponse])->preventStrayPrompts();

    $action = resolve(MealPlanAgent::class)->withDietType(DietType::Dash);
    $dayMeals = $action->generateForDay($user, 1, 7);

    expect($action->instructions())->toContain('### Sodium Restrictions');
    MealPlanAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('**Carbohydrates**: 52%')
        && $prompt->contains('**Protein**: 18%') && $prompt->contains('**Fat**: 30%'));

    expect($dayMeals)
        ->meals->toHaveCount(1)
        ->and($dayMeals->meals[0]->name)->toBe('Oatmeal');
});
