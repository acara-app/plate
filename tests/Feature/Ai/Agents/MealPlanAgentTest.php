<?php

declare(strict_types=1);

use App\Ai\Agents\MealPlanAgent;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Jobs\GenerateInitialMealPlanJob;
use App\Models\User;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\ObjectSchema;

covers(MealPlanAgent::class);

it('has the provider enforce every meal field, including calories', function (): void {
    $agent = resolve(MealPlanAgent::class);
    $schema = new ObjectSchema($agent->schema(new JsonSchemaTypeFactory), strict: true)->toSchema();

    $meal = $schema['properties']['meals']['items'];
    $ingredient = $meal['properties']['ingredients']['items'];
    $metadata = $schema['properties']['metadata'];

    expect(Strict::isAppliedTo($agent))->toBeTrue()
        ->and($schema['required'])->toEqualCanonicalizing(array_keys($schema['properties']))
        ->and($meal['required'])->toEqualCanonicalizing(array_keys($meal['properties']))
        ->and($ingredient['required'])->toEqualCanonicalizing(array_keys($ingredient['properties']))
        ->and($metadata['required'])->toEqualCanonicalizing(array_keys($metadata['properties']));
});

it('returns fluent interface when setting diet type', function (): void {
    $action = resolve(MealPlanAgent::class);
    $result = $action->withDietType(DietType::Mediterranean);

    expect($result)->toBeInstanceOf(MealPlanAgent::class);
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

it('generates meals for a single day', function (): void {
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

    MealPlanAgent::fake([$mockResponse]);

    $action = resolve(MealPlanAgent::class);
    $dayMeals = $action->generateForDay($user, 1, 7);

    expect($dayMeals)
        ->meals->toHaveCount(1)
        ->and($dayMeals->meals[0]->name)->toBe('Oatmeal');
});
