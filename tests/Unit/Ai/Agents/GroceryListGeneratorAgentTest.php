<?php

declare(strict_types=1);

use App\Ai\Agents\GroceryListGeneratorAgent;
use App\Data\ExtractedIngredientData;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;

covers(GroceryListGeneratorAgent::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = new GroceryListGeneratorAgent;
});

it('has correct attributes configured', function (): void {
    $reflection = new ReflectionClass($this->agent);

    $maxTokens = $reflection->getAttributes(MaxTokens::class);
    $timeout = $reflection->getAttributes(Timeout::class);

    expect($maxTokens)->toHaveCount(1)
        ->and($maxTokens[0]->newInstance()->value)->toBe(67000)
        ->and($timeout)->toHaveCount(1)
        ->and($timeout[0]->newInstance()->value)->toBe(120);
});

it('returns instructions with grocery list guidance', function (): void {
    $instructions = $this->agent->instructions();

    expect($instructions)
        ->toContain('grocery list optimizer')
        ->toContain('consolidate ingredients')
        ->toContain('valid JSON')
        ->toContain('Produce')
        ->toContain('Dairy')
        ->toContain('Meat & Seafood');
});

it('keeps category in english while honoring language directive', function (): void {
    $instructions = $this->agent->instructions();

    expect($instructions)
        ->toContain('"category" VALUE must be one of the English category names')
        ->toContain('"name" and "quantity" VALUES must follow the language directive');
});

it('injects users preferred language into the grocery prompt', function (): void {
    $user = User::factory()->create(['locale' => 'mn']);
    $mealPlan = MealPlan::factory()->for($user)->create(['duration_days' => 3]);

    Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Dinner',
        'ingredients' => [
            ['name' => 'chicken breast', 'quantity' => '2 lbs'],
        ],
    ]);

    $reflection = new ReflectionClass($this->agent);
    $extract = $reflection->getMethod('extractIngredients');
    $build = $reflection->getMethod('buildPrompt');

    /** @var list<ExtractedIngredientData> $ingredients */
    $ingredients = $extract->invoke($this->agent, $mealPlan);
    $prompt = (string) $build->invoke($this->agent, $ingredients, $mealPlan);

    expect($prompt)
        ->toContain('LANGUAGE:')
        ->toContain('Монгол')
        ->toContain('(language code: `mn`)')
        ->toContain('"category" value MUST stay in English');
});

it('defaults grocery prompt language to english when user has none', function (): void {
    $user = User::factory()->create(['locale' => null]);
    $mealPlan = MealPlan::factory()->for($user)->create(['duration_days' => 3]);

    Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Dinner',
        'ingredients' => [
            ['name' => 'rice', 'quantity' => '1 cup'],
        ],
    ]);

    $reflection = new ReflectionClass($this->agent);
    $extract = $reflection->getMethod('extractIngredients');
    $build = $reflection->getMethod('buildPrompt');

    /** @var list<ExtractedIngredientData> $ingredients */
    $ingredients = $extract->invoke($this->agent, $mealPlan);
    $prompt = (string) $build->invoke($this->agent, $ingredients, $mealPlan);

    expect($prompt)
        ->toContain('LANGUAGE:')
        ->toContain('English')
        ->toContain('(language code: `en`)');
});

it('generates grocery list from meal plan with ingredients', function (): void {
    $mockResponse = [
        'items' => [
            ['name' => 'Chicken Breast', 'quantity' => '2 lbs', 'category' => 'Meat & Seafood'],
            ['name' => 'Olive Oil', 'quantity' => '2 tbsp', 'category' => 'Condiments & Sauces'],
        ],
    ];

    GroceryListGeneratorAgent::fake([$mockResponse]);

    $mealPlan = MealPlan::factory()->for($this->user)->create(['duration_days' => 7]);

    Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Dinner',
        'ingredients' => [
            ['name' => 'chicken breast', 'quantity' => '2 lbs'],
            ['name' => 'olive oil', 'quantity' => '2 tbsp'],
        ],
    ]);

    $result = $this->agent->generate($mealPlan);

    expect($result->items)->toHaveCount(2)
        ->and($result->items->first()->name)->toBe('Chicken Breast')
        ->and($result->items->first()->quantity)->toBe('2 lbs')
        ->and($result->items->first()->category)->toBe('Meat & Seafood');
});

it('returns empty list when meal plan has no meals', function (): void {
    $mealPlan = MealPlan::factory()->for($this->user)->create(['duration_days' => 7]);

    $result = $this->agent->generate($mealPlan);

    expect($result->items)->toHaveCount(0);
});

it('returns empty list when meals have no ingredients', function (): void {
    $mealPlan = MealPlan::factory()->for($this->user)->create(['duration_days' => 7]);

    Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Dinner',
        'ingredients' => null,
    ]);

    $result = $this->agent->generate($mealPlan);

    expect($result->items)->toHaveCount(0);
});

it('returns empty list when meals have empty ingredients array', function (): void {
    $mealPlan = MealPlan::factory()->for($this->user)->create(['duration_days' => 7]);

    Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Dinner',
        'ingredients' => [],
    ]);

    $result = $this->agent->generate($mealPlan);

    expect($result->items)->toHaveCount(0);
});

it('extracts ingredients from multiple meals', function (): void {
    $mockResponse = [
        'items' => [
            ['name' => 'Eggs', 'quantity' => '18', 'category' => 'Dairy'],
            ['name' => 'Bread', 'quantity' => '1 loaf', 'category' => 'Bakery'],
        ],
    ];

    GroceryListGeneratorAgent::fake([$mockResponse]);

    $mealPlan = MealPlan::factory()->for($this->user)->create(['duration_days' => 7]);

    Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Breakfast',
        'ingredients' => [
            ['name' => 'eggs', 'quantity' => '6'],
        ],
    ]);

    Meal::factory()->for($mealPlan)->create([
        'day_number' => 2,
        'name' => 'Breakfast',
        'ingredients' => [
            ['name' => 'eggs', 'quantity' => '6'],
            ['name' => 'bread', 'quantity' => '2 slices'],
        ],
    ]);

    Meal::factory()->for($mealPlan)->create([
        'day_number' => 3,
        'name' => 'Breakfast',
        'ingredients' => [
            ['name' => 'eggs', 'quantity' => '6'],
        ],
    ]);

    $result = $this->agent->generate($mealPlan);

    expect($result->items)->toHaveCount(2);
});

it('handles json with markdown code blocks', function (): void {
    $mockResponse = [
        'items' => [
            ['name' => 'Rice', 'quantity' => '2 cups', 'category' => 'Pantry'],
        ],
    ];

    GroceryListGeneratorAgent::fake([$mockResponse]);

    $mealPlan = MealPlan::factory()->for($this->user)->create(['duration_days' => 7]);

    Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Dinner',
        'ingredients' => [
            ['name' => 'rice', 'quantity' => '2 cups'],
        ],
    ]);

    $result = $this->agent->generate($mealPlan);

    expect($result->items)->toHaveCount(1)
        ->and($result->items->first()->name)->toBe('Rice');
});
