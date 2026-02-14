<?php

declare(strict_types=1);

use App\Ai\Agents\GroceryListGeneratorAgent;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = new GroceryListGeneratorAgent;
});

it('returns correct max tokens', function (): void {
    expect($this->agent->maxTokens())->toBe(67000);
});

it('returns client options with timeout', function (): void {
    $options = $this->agent->clientOptions();

    expect($options)->toHaveKey('timeout')
        ->and($options['timeout'])->toBe(120);
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
