<?php

declare(strict_types=1);

use App\Ai\Agents\GroceryListGeneratorAgent;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = new GroceryListGeneratorAgent;
    Config::set('prism.providers.gemini.api_key', 'test-key');
});

it('returns gemini as provider', function (): void {
    expect($this->agent->provider())->toBe(Provider::Gemini);
});

it('returns correct model', function (): void {
    expect($this->agent->model())->toBe('gemini-3-flash-preview');
});

it('returns system prompt with instructions', function (): void {
    $systemPrompt = $this->agent->systemPrompt();

    expect($systemPrompt)
        ->toContain('grocery list optimizer')
        ->toContain('consolidate ingredients')
        ->toContain('valid JSON')
        ->toContain('Produce')
        ->toContain('Dairy')
        ->toContain('Meat & Seafood');
});

it('returns correct max tokens', function (): void {
    expect($this->agent->maxTokens())->toBe(67000);
});

it('returns client options with timeout', function (): void {
    $options = $this->agent->clientOptions();

    expect($options)->toHaveKey('timeout')
        ->and($options['timeout'])->toBe(120);
});

it('generates grocery list from meal plan with ingredients', function (): void {
    $fakeResponse = TextResponseFake::make()
        ->withText('{"items": [{"name": "Chicken Breast", "quantity": "2 lbs", "category": "Meat & Seafood"}, {"name": "Olive Oil", "quantity": "2 tbsp", "category": "Condiments & Sauces"}]}')
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-3-flash-preview'));

    Prism::fake([$fakeResponse]);

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
    $fakeResponse = TextResponseFake::make()
        ->withText('{"items": [{"name": "Eggs", "quantity": "18", "category": "Dairy"}, {"name": "Bread", "quantity": "1 loaf", "category": "Bakery"}]}')
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(150, 250))
        ->withMeta(new Meta('test-id', 'gemini-3-flash-preview'));

    Prism::fake([$fakeResponse]);

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
    $fakeResponse = TextResponseFake::make()
        ->withText('```json
{"items": [{"name": "Rice", "quantity": "2 cups", "category": "Pantry"}]}
```')
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(80, 100))
        ->withMeta(new Meta('test-id', 'gemini-2.5-flash'));

    Prism::fake([$fakeResponse]);

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
