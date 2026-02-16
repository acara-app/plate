<?php

declare(strict_types=1);

use App\Actions\GetUserProfileContextAction;
use App\Ai\Agents\SingleMealAgent;
use App\Ai\SingleMealPromptBuilder;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\TestJsonSchema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.5,
    ]);

    $profileContext = new GetUserProfileContextAction;
    $this->promptBuilder = new SingleMealPromptBuilder($profileContext);
    $this->agent = new SingleMealAgent($this->promptBuilder);
});

it('returns correct instructions', function (): void {
    $instructions = $this->agent->instructions();

    expect($instructions)
        ->toContain('professional nutritionist and chef')
        ->toContain('healthy, delicious meals')
        ->toContain('dietary needs and health conditions')
        ->toContain('glucose impact');
});

it('returns max tokens', function (): void {
    $maxTokens = $this->agent->maxTokens();

    expect($maxTokens)->toBe(8000);
});

it('returns client options', function (): void {
    $options = $this->agent->clientOptions();

    expect($options)->toBeArray()
        ->and($options)->toHaveKey('timeout')
        ->and($options['timeout'])->toBe(60);
});

it('returns valid schema with all fields', function (): void {
    $schema = new TestJsonSchema;
    $result = $this->agent->schema($schema);

    expect($result)->toBeArray()
        ->toHaveKeys([
            'name',
            'description',
            'meal_type',
            'cuisine',
            'calories',
            'protein_grams',
            'carbs_grams',
            'fat_grams',
            'fiber_grams',
            'ingredients',
            'instructions',
            'prep_time_minutes',
            'cook_time_minutes',
            'servings',
            'dietary_tags',
            'glycemic_index_estimate',
            'glucose_impact_notes',
        ]);

    expect($result['name'])->not->toBeNull()
        ->and($result['meal_type'])->not->toBeNull()
        ->and($result['calories'])->not->toBeNull()
        ->and($result['protein_grams'])->not->toBeNull()
        ->and($result['carbs_grams'])->not->toBeNull()
        ->and($result['fat_grams'])->not->toBeNull();
});

it('generates a meal with fake response', function (): void {
    $mockResponse = [
        'name' => 'Grilled Salmon Salad',
        'description' => 'Fresh salmon with mixed greens',
        'meal_type' => 'lunch',
        'cuisine' => 'Mediterranean',
        'calories' => 450,
        'protein_grams' => 35,
        'carbs_grams' => 25,
        'fat_grams' => 18,
        'fiber_grams' => 8,
        'ingredients' => ['salmon', 'lettuce', 'tomato', 'olive oil'],
        'instructions' => ['Grill salmon', 'Toss salad', 'Combine'],
        'prep_time_minutes' => 15,
        'cook_time_minutes' => 10,
        'servings' => 1,
        'dietary_tags' => ['gluten-free', 'high-protein'],
        'glycemic_index_estimate' => 'Low (GI < 55)',
        'glucose_impact_notes' => 'Minimal impact on blood sugar',
    ];

    SingleMealAgent::fake([$mockResponse]);

    $mealData = $this->agent->generate(
        $this->user,
        'lunch',
        'Mediterranean',
        500,
        'healthy and light'
    );

    expect($mealData)
        ->name->toBe('Grilled Salmon Salad')
        ->mealType->toBe('lunch')
        ->cuisine->toBe('Mediterranean')
        ->calories->toBe(450.0)
        ->proteinGrams->toBe(35.0)
        ->carbsGrams->toBe(25.0)
        ->fatGrams->toBe(18.0)
        ->fiberGrams->toBe(8.0)
        ->servings->toBe(1)
        ->description->toBe('Fresh salmon with mixed greens')
        ->glycemicIndexEstimate->toBe('Low (GI < 55)')
        ->glucoseImpactNotes->toBe('Minimal impact on blood sugar');

    expect($mealData->ingredients)->toBe(['salmon', 'lettuce', 'tomato', 'olive oil'])
        ->and($mealData->instructions)->toBe(['Grill salmon', 'Toss salad', 'Combine'])
        ->and($mealData->dietaryTags)->toBe(['gluten-free', 'high-protein']);
});

it('generates a meal with minimal data', function (): void {
    $mockResponse = [
        'name' => 'Simple Oatmeal',
        'description' => null,
        'meal_type' => 'breakfast',
        'cuisine' => null,
        'calories' => 300,
        'protein_grams' => 10,
        'carbs_grams' => 50,
        'fat_grams' => 5,
    ];

    SingleMealAgent::fake([$mockResponse]);

    $mealData = $this->agent->generate(
        $this->user,
        'breakfast'
    );

    expect($mealData)
        ->name->toBe('Simple Oatmeal')
        ->mealType->toBe('breakfast')
        ->calories->toBe(300.0)
        ->proteinGrams->toBe(10.0)
        ->carbsGrams->toBe(50.0)
        ->fatGrams->toBe(5.0)
        ->cuisine->toBeNull()
        ->description->toBeNull();
});

it('generates a meal with all optional parameters', function (): void {
    $mockResponse = [
        'name' => 'Spicy Thai Curry',
        'meal_type' => 'dinner',
        'cuisine' => 'Thai',
        'calories' => 600,
        'protein_grams' => 30,
        'carbs_grams' => 45,
        'fat_grams' => 25,
    ];

    SingleMealAgent::fake([$mockResponse]);

    $mealData = $this->agent->generate(
        $this->user,
        'dinner',
        'Thai',
        700,
        'spicy and flavorful'
    );

    expect($mealData->name)->toBe('Spicy Thai Curry');
});
