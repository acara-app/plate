<?php

declare(strict_types=1);

use App\Ai\Agents\MealPlanAgent;
use App\Ai\Tools\StartMealPlanGeneration;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Jobs\GenerateInitialMealPlanJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\ToolChoice;
use Laravel\Ai\Tools\LoadSkill;

covers(MealPlanAgent::class);

it('can look up diet references without starting another plan while generating meals', function (): void {
    $agent = resolve(MealPlanAgent::class);
    $tools = array_map(get_class(...), LoadSkill::mergeInto($agent->tools(), $agent));
    $options = TextGenerationOptions::forAgent($agent);

    expect($tools)->toContain(LoadSkill::class)->not->toContain(StartMealPlanGeneration::class);
    expect($options->toolChoice?->mode)->toBe(ToolChoice::tool)
        ->and($options->toolChoice?->toolName)->toBe('LoadSkill');
});

it('offers only the skill for the selected diet', function (DietType $diet, string $name): void {
    $planner = resolve(MealPlanAgent::class)->withDietType($diet);

    expect(collect($planner->skills())->sole()->name)->toBe($name);
})->with([
    [DietType::Balanced, 'balanced-diet'],
    [DietType::Dash, 'dash-diet'],
    [DietType::Keto, 'ketogenic-diet'],
    [DietType::LowCarb, 'low-carb-diet'],
    [DietType::Mediterranean, 'mediterranean-diet'],
    [DietType::Paleo, 'paleo-diet'],
    [DietType::Vegan, 'vegan-diet'],
    [DietType::Vegetarian, 'vegetarian-diet'],
]);

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

    MealPlanAgent::fake([
        new ToolCall('read-dash', 'LoadSkill', ['name' => 'dash-diet']),
        $mockResponse,
    ])->preventStrayPrompts();

    $action = resolve(MealPlanAgent::class)->withDietType(DietType::Dash);
    $dayMeals = $action->generateForDay($user, 1, 7);

    expect($action->instructions())->toContain('dash-diet')->not->toContain('## Sodium Restrictions');
    MealPlanAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('**Carbohydrates**: 52%')
        && $prompt->contains('**Protein**: 18%') && $prompt->contains('**Fat**: 30%'));

    expect($dayMeals)
        ->meals->toHaveCount(1)
        ->and($dayMeals->meals[0]->name)->toBe('Oatmeal');
});
