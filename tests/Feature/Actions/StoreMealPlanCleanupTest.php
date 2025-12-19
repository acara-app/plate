<?php

declare(strict_types=1);

use App\Actions\StoreMealPlan;
use App\DataObjects\MealData;
use App\DataObjects\MealPlanData;
use App\Enums\MealPlanType;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;
use Spatie\LaravelData\DataCollection;

it('deletes old meal plans of the same type when creating a new one', function (): void {
    $user = User::factory()->create();

    // Create an old weekly meal plan
    $oldPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1)->count(3), 'meals')
        ->create(['name' => 'Old Weekly Plan']);

    expect(MealPlan::query()->count())->toBe(1);
    expect(Meal::query()->count())->toBe(3);

    // Create a new weekly meal plan using the action
    $mealPlanData = new MealPlanData(
        type: MealPlanType::Weekly,
        name: 'New Weekly Plan',
        description: 'A new meal plan',
        durationDays: 7,
        targetDailyCalories: 2000.0,
        macronutrientRatios: ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        meals: MealData::collect([
            MealData::from([
                'day_number' => 1,
                'type' => 'breakfast',
                'name' => 'Oatmeal',
                'description' => 'Healthy breakfast',
                'preparation_instructions' => 'Cook oats',
                'ingredients' => [['name' => 'Oats', 'quantity' => '50g'], ['name' => 'Milk', 'quantity' => '200ml']],
                'portion_size' => '1 bowl',
                'calories' => 350.0,
                'protein_grams' => 10.0,
                'carbs_grams' => 60.0,
                'fat_grams' => 5.0,
                'preparation_time_minutes' => 10,
                'sort_order' => 1,
            ]),
        ], DataCollection::class),
    );

    $action = resolve(StoreMealPlan::class);
    $newPlan = $action->handle($user, $mealPlanData);

    // Verify old plan was deleted
    expect(MealPlan::query()->count())->toBe(1);
    expect(MealPlan::query()->first()->id)->toBe($newPlan->id);
    expect(MealPlan::query()->first()->name)->toBe('New Weekly Plan');

    // Verify old meals were also deleted (cascade)
    expect(Meal::query()->count())->toBe(1);

    // Verify we can't find the old plan
    expect(MealPlan::query()->find($oldPlan->id))->toBeNull();
});

it('only deletes meal plans of the same type', function (): void {
    $user = User::factory()->create();

    // Create meal plans of different types
    $weeklyPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create(['name' => 'Weekly Plan']);

    $monthlyPlan = MealPlan::factory()
        ->monthly()
        ->for($user)
        ->has(Meal::factory()->lunch()->forDay(1), 'meals')
        ->create(['name' => 'Monthly Plan']);

    expect(MealPlan::query()->count())->toBe(2);
    expect(Meal::query()->count())->toBe(2);

    // Create a new weekly meal plan
    $mealPlanData = new MealPlanData(
        type: MealPlanType::Weekly,
        name: 'New Weekly Plan',
        description: 'A new weekly plan',
        durationDays: 7,
        targetDailyCalories: 2000.0,
        macronutrientRatios: ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        meals: MealData::collect([
            MealData::from([
                'day_number' => 1,
                'type' => 'breakfast',
                'name' => 'Eggs',
                'description' => 'Protein breakfast',
                'preparation_instructions' => 'Scramble eggs',
                'ingredients' => [['name' => 'Eggs', 'quantity' => '2 eggs'], ['name' => 'Butter', 'quantity' => '10g']],
                'portion_size' => '2 eggs',
                'calories' => 200.0,
                'protein_grams' => 15.0,
                'carbs_grams' => 2.0,
                'fat_grams' => 14.0,
                'preparation_time_minutes' => 5,
                'sort_order' => 1,
            ]),
        ], DataCollection::class),
    );

    $action = resolve(StoreMealPlan::class);
    $action->handle($user, $mealPlanData);

    // Verify weekly plan was replaced but monthly plan remains
    expect(MealPlan::query()->count())->toBe(2);
    expect(MealPlan::query()->where('type', MealPlanType::Weekly)->count())->toBe(1);
    expect(MealPlan::query()->where('type', MealPlanType::Monthly)->count())->toBe(1);

    // Verify old weekly plan is gone
    expect(MealPlan::query()->find($weeklyPlan->id))->toBeNull();

    // Verify monthly plan still exists
    expect(MealPlan::query()->find($monthlyPlan->id))->not->toBeNull();
});

it('deletes multiple old meal plans of the same type', function (): void {
    $user = User::factory()->create();

    // Create multiple old weekly meal plans
    $oldPlan1 = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create(['name' => 'Old Plan 1', 'created_at' => now()->subDays(10)]);

    $oldPlan2 = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->lunch()->forDay(1), 'meals')
        ->create(['name' => 'Old Plan 2', 'created_at' => now()->subDays(5)]);

    $oldPlan3 = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->dinner()->forDay(1), 'meals')
        ->create(['name' => 'Old Plan 3', 'created_at' => now()->subDays(1)]);

    expect(MealPlan::query()->count())->toBe(3);
    expect(Meal::query()->count())->toBe(3);

    // Create a new weekly meal plan
    $mealPlanData = new MealPlanData(
        type: MealPlanType::Weekly,
        name: 'Latest Plan',
        description: 'The newest plan',
        durationDays: 7,
        targetDailyCalories: 2000.0,
        macronutrientRatios: ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        meals: MealData::collect([
            MealData::from([
                'day_number' => 1,
                'type' => 'breakfast',
                'name' => 'Toast',
                'description' => 'Simple breakfast',
                'preparation_instructions' => 'Toast bread',
                'ingredients' => [['name' => 'Bread', 'quantity' => '2 slices'], ['name' => 'Butter', 'quantity' => '10g']],
                'portion_size' => '2 slices',
                'calories' => 150.0,
                'protein_grams' => 5.0,
                'carbs_grams' => 20.0,
                'fat_grams' => 5.0,
                'preparation_time_minutes' => 3,
                'sort_order' => 1,
            ]),
        ], DataCollection::class),
    );

    $action = resolve(StoreMealPlan::class);
    $newPlan = $action->handle($user, $mealPlanData);

    // Verify all old plans were deleted
    expect(MealPlan::query()->count())->toBe(1);
    expect(MealPlan::query()->first()->id)->toBe($newPlan->id);
    expect(MealPlan::query()->first()->name)->toBe('Latest Plan');

    // Verify all old meals were also deleted
    expect(Meal::query()->count())->toBe(1);

    // Verify we can't find any old plans
    expect(MealPlan::query()->find($oldPlan1->id))->toBeNull();
    expect(MealPlan::query()->find($oldPlan2->id))->toBeNull();
    expect(MealPlan::query()->find($oldPlan3->id))->toBeNull();
});

it('does not delete other users meal plans', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Create meal plans for both users
    $user1Plan = MealPlan::factory()
        ->weekly()
        ->for($user1)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create(['name' => 'User 1 Plan']);

    $user2Plan = MealPlan::factory()
        ->weekly()
        ->for($user2)
        ->has(Meal::factory()->lunch()->forDay(1), 'meals')
        ->create(['name' => 'User 2 Plan']);

    expect(MealPlan::query()->count())->toBe(2);

    // Create a new weekly meal plan for user 1
    $mealPlanData = new MealPlanData(
        type: MealPlanType::Weekly,
        name: 'User 1 New Plan',
        description: 'New plan for user 1',
        durationDays: 7,
        targetDailyCalories: 2000.0,
        macronutrientRatios: ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        meals: MealData::collect([
            MealData::from([
                'day_number' => 1,
                'type' => 'breakfast',
                'name' => 'Cereal',
                'description' => 'Quick breakfast',
                'preparation_instructions' => 'Pour cereal',
                'ingredients' => [['name' => 'Cereal', 'quantity' => '50g'], ['name' => 'Milk', 'quantity' => '200ml']],
                'portion_size' => '1 bowl',
                'calories' => 250.0,
                'protein_grams' => 8.0,
                'carbs_grams' => 40.0,
                'fat_grams' => 3.0,
                'preparation_time_minutes' => 2,
                'sort_order' => 1,
            ]),
        ], DataCollection::class),
    );

    $action = resolve(StoreMealPlan::class);
    $action->handle($user1, $mealPlanData);

    // Verify user 1's old plan was deleted but user 2's plan remains
    expect(MealPlan::query()->count())->toBe(2);
    expect(MealPlan::query()->where('user_id', $user1->id)->count())->toBe(1);
    expect(MealPlan::query()->where('user_id', $user2->id)->count())->toBe(1);

    // Verify user 1's old plan is gone
    expect(MealPlan::query()->find($user1Plan->id))->toBeNull();

    // Verify user 2's plan still exists
    expect(MealPlan::query()->find($user2Plan->id))->not->toBeNull();
});

it('handles creating first meal plan when no old plans exist', function (): void {
    $user = User::factory()->create();

    expect(MealPlan::query()->count())->toBe(0);

    // Create first meal plan
    $mealPlanData = new MealPlanData(
        type: MealPlanType::Weekly,
        name: 'First Plan',
        description: 'The first plan',
        durationDays: 7,
        targetDailyCalories: 2000.0,
        macronutrientRatios: ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        meals: MealData::collect([
            MealData::from([
                'day_number' => 1,
                'type' => 'breakfast',
                'name' => 'Smoothie',
                'description' => 'Fruit smoothie',
                'preparation_instructions' => 'Blend ingredients',
                'ingredients' => [['name' => 'Banana', 'quantity' => '1 whole'], ['name' => 'Berries', 'quantity' => '100g'], ['name' => 'Yogurt', 'quantity' => '150g']],
                'portion_size' => '1 glass',
                'calories' => 300.0,
                'protein_grams' => 12.0,
                'carbs_grams' => 50.0,
                'fat_grams' => 4.0,
                'preparation_time_minutes' => 5,
                'sort_order' => 1,
            ]),
        ], DataCollection::class),
    );

    $action = resolve(StoreMealPlan::class);
    $mealPlan = $action->handle($user, $mealPlanData);

    // Verify meal plan was created successfully
    expect(MealPlan::query()->count())->toBe(1);
    expect($mealPlan->name)->toBe('First Plan');
    expect(Meal::query()->count())->toBe(1);
});

it('cascades delete to meals when deleting old meal plans', function (): void {
    $user = User::factory()->create();

    // Create an old weekly meal plan with many meals
    $oldPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create(['name' => 'Old Plan']);

    // Create 21 meals (3 meals per day for 7 days)
    for ($day = 1; $day <= 7; $day++) {
        Meal::factory()->breakfast()->forDay($day)->for($oldPlan)->create();
        Meal::factory()->lunch()->forDay($day)->for($oldPlan)->create();
        Meal::factory()->dinner()->forDay($day)->for($oldPlan)->create();
    }

    expect(MealPlan::query()->count())->toBe(1);
    expect(Meal::query()->count())->toBe(21);

    // Store IDs to verify they're deleted
    $oldMealIds = Meal::query()->pluck('id')->toArray();

    // Create a new weekly meal plan with just 1 meal
    $mealPlanData = new MealPlanData(
        type: MealPlanType::Weekly,
        name: 'New Plan',
        description: 'A new plan',
        durationDays: 7,
        targetDailyCalories: 2000.0,
        macronutrientRatios: ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        meals: MealData::collect([
            MealData::from([
                'day_number' => 1,
                'type' => 'breakfast',
                'name' => 'New Breakfast',
                'description' => 'New meal',
                'preparation_instructions' => 'Cook',
                'ingredients' => [['name' => 'Generic ingredient', 'quantity' => '100g']],
                'portion_size' => '1 serving',
                'calories' => 300.0,
                'protein_grams' => 20.0,
                'carbs_grams' => 30.0,
                'fat_grams' => 10.0,
                'preparation_time_minutes' => 15,
                'sort_order' => 1,
            ]),
        ], DataCollection::class),
    );

    $action = resolve(StoreMealPlan::class);
    $newPlan = $action->handle($user, $mealPlanData);

    // Verify old plan and ALL its meals were deleted
    expect(MealPlan::query()->count())->toBe(1);
    expect(MealPlan::query()->first()->id)->toBe($newPlan->id);
    expect(Meal::query()->count())->toBe(1);

    // Verify all old meals are gone
    foreach ($oldMealIds as $oldMealId) {
        expect(Meal::query()->find($oldMealId))->toBeNull();
    }

    // Verify only the new meal exists
    expect(Meal::query()->first()->name)->toBe('New Breakfast');
});
