<?php

declare(strict_types=1);

use App\Ai\Tools\SuggestSingleMeal;
use App\Contracts\Ai\GeneratesSingleMeals;
use App\DataObjects\GeneratedMealData;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

beforeEach(function (): void {
    // Create a simple test implementation
    $this->agent = new class implements GeneratesSingleMeals
    {
        public ?GeneratedMealData $mealData = null;

        public ?Exception $exception = null;

        public array $calls = [];

        public function generate(
            User $user,
            string $mealType,
            ?string $cuisine = null,
            ?int $maxCalories = null,
            ?string $specificRequest = null,
        ): GeneratedMealData {
            $this->calls[] = [
                'user' => $user,
                'mealType' => $mealType,
                'cuisine' => $cuisine,
                'maxCalories' => $maxCalories,
                'specificRequest' => $specificRequest,
            ];

            if ($this->exception instanceof Exception) {
                throw $this->exception;
            }

            return $this->mealData ?? new GeneratedMealData(
                name: 'Default Meal',
                mealType: $mealType,
                calories: 300,
                proteinGrams: 20,
                cuisine: $cuisine,
                description: 'Test meal',
                carbsGrams: 30,
                fatGrams: 10
            );
        }
    };

    $this->tool = new SuggestSingleMeal($this->agent);
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('suggest_meal')
        ->and($this->tool->description())->toContain('Generate a personalized meal');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;

    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['meal_type', 'cuisine', 'max_calories']);
});

it('returns error if user is not authenticated', function (): void {
    $request = new Request(['meal_type' => 'lunch']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'User not authenticated');
});

it('generates meal successfully', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->agent->mealData = new GeneratedMealData(
        name: 'Taco',
        mealType: 'lunch',
        calories: 300,
        proteinGrams: 20,
        cuisine: 'Mexican',
        description: 'Tasty taco',
        carbsGrams: 30,
        fatGrams: 10
    );

    $request = new Request([
        'meal_type' => 'lunch',
        'cuisine' => 'Mexican',
        'max_calories' => 500,
        'specific_request' => 'spicy',
    ]);

    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['meal']['name'])->toBe('Taco');

    expect($this->agent->calls[0]['mealType'])->toBe('lunch');
    expect($this->agent->calls[0]['cuisine'])->toBe('Mexican');
    expect($this->agent->calls[0]['maxCalories'])->toBe(500);
    expect($this->agent->calls[0]['specificRequest'])->toBe('spicy');
});

it('handles exceptions during generation', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->agent->exception = new Exception('Agent error');

    $request = new Request(['meal_type' => 'lunch']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error')
        ->and($json['error'])->toContain('Agent error');
});
