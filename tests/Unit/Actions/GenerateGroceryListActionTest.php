<?php

declare(strict_types=1);

use App\Actions\GenerateGroceryListAction;
use App\Enums\GroceryListStatus;
use App\Models\GroceryList;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->mealPlan = MealPlan::factory()->for($this->user)->create([
        'duration_days' => 7,
        'name' => 'Weekly Plan',
    ]);

    // Set up Prism fake for AI interactions
    Config::set('prism.providers.gemini.api_key', 'test-key');
});

it('creates a placeholder grocery list', function (): void {
    $action = resolve(GenerateGroceryListAction::class);

    $groceryList = $action->createPlaceholder($this->mealPlan);

    expect($groceryList)->toBeInstanceOf(GroceryList::class)
        ->and($groceryList->user_id)->toBe($this->user->id)
        ->and($groceryList->meal_plan_id)->toBe($this->mealPlan->id)
        ->and($groceryList->name)->toBe("Grocery List for {$this->mealPlan->name}")
        ->and($groceryList->status)->toBe(GroceryListStatus::Generating)
        ->and($groceryList->metadata)->toHaveKey('started_at')
        ->and($groceryList->metadata)->toHaveKey('meal_plan_duration_days')
        ->and($groceryList->metadata['meal_plan_duration_days'])->toBe(7);
});

it('deletes existing grocery list before creating placeholder', function (): void {
    $existingList = GroceryList::factory()->for($this->mealPlan)->for($this->user)->create();

    $action = resolve(GenerateGroceryListAction::class);
    $groceryList = $action->createPlaceholder($this->mealPlan);

    expect(GroceryList::query()->find($existingList->id))->toBeNull()
        ->and($groceryList->id)->not->toBe($existingList->id);
});

it('generates items for grocery list successfully', function (): void {
    $fakeResponse = TextResponseFake::make()
        ->withText('{"items": [{"name": "Chicken Breast", "quantity": "2 lbs", "category": "Meat & Seafood"}, {"name": "Olive Oil", "quantity": "2 tbsp", "category": "Condiments & Sauces"}]}')
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-2.5-flash'));

    Prism::fake([$fakeResponse]);

    Meal::factory()->for($this->mealPlan)->create([
        'ingredients' => [
            ['name' => 'chicken breast', 'quantity' => '2 lbs'],
            ['name' => 'olive oil', 'quantity' => '2 tbsp'],
        ],
    ]);

    $groceryList = GroceryList::factory()
        ->for($this->mealPlan)
        ->for($this->user)
        ->create(['status' => GroceryListStatus::Generating]);

    $action = resolve(GenerateGroceryListAction::class);
    $result = $action->generateItems($groceryList);

    expect($result)->toBeInstanceOf(GroceryList::class)
        ->and($result->status)->toBe(GroceryListStatus::Active)
        ->and($result->items)->toHaveCount(2)
        ->and($result->metadata)->toHaveKey('completed_at')
        ->and($result->metadata)->toHaveKey('total_items')
        ->and($result->metadata['total_items'])->toBe(2);

    expect($result->items)->toHaveCount(2);

    $firstItem = $result->items->sortBy('sort_order')->first();
    expect($firstItem)
        ->is_checked->toBeFalse()
        ->sort_order->toBe(0);
});

it('generates items with no ingredients returns empty list', function (): void {
    $fakeResponse = TextResponseFake::make()
        ->withText('{"items": []}')
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(50, 10))
        ->withMeta(new Meta('test-id', 'gemini-2.5-flash'));

    Prism::fake([$fakeResponse]);

    $groceryList = GroceryList::factory()
        ->for($this->mealPlan)
        ->for($this->user)
        ->create(['status' => GroceryListStatus::Generating]);

    $action = resolve(GenerateGroceryListAction::class);
    $result = $action->generateItems($groceryList);

    expect($result)->toBeInstanceOf(GroceryList::class)
        ->and($result->status)->toBe(GroceryListStatus::Active)
        ->and($result->items)->toHaveCount(0);
});

it('handles generation failure gracefully', function (): void {
    $fakeResponse = TextResponseFake::make()
        ->withText('invalid json{}}')
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(50, 10))
        ->withMeta(new Meta('test-id', 'gemini-2.5-flash'));

    Prism::fake([$fakeResponse]);

    Meal::factory()->for($this->mealPlan)->create([
        'ingredients' => [
            ['name' => 'chicken breast', 'quantity' => '2 lbs'],
        ],
    ]);

    $groceryList = GroceryList::factory()
        ->for($this->mealPlan)
        ->for($this->user)
        ->create(['status' => GroceryListStatus::Generating]);

    $action = resolve(GenerateGroceryListAction::class);
    $result = $action->generateItems($groceryList);

    expect($result->status)->toBe(GroceryListStatus::Failed)
        ->and($result->metadata)->toHaveKey('failed_at')
        ->and($result->metadata)->toHaveKey('error');
});

it('handles full generation with handle method', function (): void {
    $fakeResponse = TextResponseFake::make()
        ->withText('{"items": [{"name": "Eggs", "quantity": "12", "category": "Dairy"}]}')
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(80, 50))
        ->withMeta(new Meta('test-id', 'gemini-2.5-flash'));

    Prism::fake([$fakeResponse]);

    Meal::factory()->for($this->mealPlan)->create([
        'ingredients' => [
            ['name' => 'eggs', 'quantity' => '12'],
        ],
    ]);

    $action = resolve(GenerateGroceryListAction::class);
    $result = $action->handle($this->mealPlan);

    expect($result)->toBeInstanceOf(GroceryList::class)
        ->and($result->status)->toBe(GroceryListStatus::Active)
        ->and($result->items)->toHaveCount(1);
});
