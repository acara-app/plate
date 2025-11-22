<?php

declare(strict_types=1);

use App\Actions\StoreMealPlan;
use App\DataObjects\MealData;
use App\DataObjects\MealPlanData;
use App\Enums\MealPlanType;
use App\Enums\MealType;
use App\Models\User;

it('stores a meal plan with meals for a user', function (): void {
    $user = User::factory()->create();

    $mealPlanData = new MealPlanData(
        type: MealPlanType::Weekly,
        name: 'Test Weekly Plan',
        description: 'A test meal plan',
        durationDays: 7,
        targetDailyCalories: 2000.0,
        macronutrientRatios: ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        meals: [
            MealData::from([
                'day_number'=>1,
                'type'=>'breakfast',
                'name'=>'Oatmeal with Berries',
                'description'=>'Healthy breakfast',
                'preparation_instructions'=>'Cook oats, add berries',
                'ingredients'=>[['name' => 'Oats', 'quantity' => '50g'], ['name' => 'Berries', 'quantity' => '100g'], ['name' => 'Milk', 'quantity' => '200ml']],
                'portion_size'=>'1 bowl',
                'calories'=>350.0,
                'protein_grams'=>10.0,
                'carbs_grams'=>60.0,
                'fat_grams'=>5.0,
                'preparation_time_minutes'=>10,
                'sort_order'=>1,
        ]),
            MealData::from([
                'day_number'=>1,
                'type'=>'lunch',
                'name'=>'Chicken Salad',
                'description'=>'Protein-rich lunch',
                'preparation_instructions'=>'Grill chicken, mix with greens',
                'ingredients'=>[['name' => 'Chicken breast', 'quantity' => '150g'], ['name' => 'Mixed greens', 'quantity' => '100g'], ['name' => 'Olive oil', 'quantity' => '15ml']],
                'portion_size'=>'1 plate',
                'calories'=>450.0,
                'protein_grams'=>40.0,
                'carbs_grams'=>20.0,
                'fat_grams'=>15.0,
                'preparation_time_minutes'=>20,
                'sort_order'=>2,
        ]),
        ],
    );

    $action = app(StoreMealPlan::class);
    $mealPlan = $action->handle($user, $mealPlanData);

    expect($mealPlan)
        ->type->toBe(MealPlanType::Weekly)
        ->name->toBe('Test Weekly Plan')
        ->description->toBe('A test meal plan')
        ->duration_days->toBe(7)
        ->target_daily_calories->toBe('2000.00')
        ->macronutrient_ratios->toBe(['protein' => 30, 'carbs' => 40, 'fat' => 30])
        ->meals->toHaveCount(2);

    expect($mealPlan->meals->first())
        ->day_number->toBe(1)
        ->type->toBe(MealType::Breakfast)
        ->name->toBe('Oatmeal with Berries')
        ->calories->toBe('350.00');

    expect($mealPlan->meals->last())
        ->day_number->toBe(1)
        ->type->toBe(MealType::Lunch)
        ->name->toBe('Chicken Salad')
        ->calories->toBe('450.00');
});

it('loads meals relationship after storing', function (): void {
    $user = User::factory()->create();

    $mealPlanData = new MealPlanData(
        type: MealPlanType::Weekly,
        name: 'Test Plan',
        description: 'Test',
        durationDays: 7,
        targetDailyCalories: 2000.0,
        macronutrientRatios: ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        meals: [
            MealData::from([
                'day_number'=>1,
                'type'=>'breakfast',
                'name'=>'Meal 1',
                'description'=>'Test',
                'preparation_instructions'=>'Test',
                'ingredients'=>[['name' => 'Test ingredient', 'quantity' => '100g']],
                'portion_size'=>'1 serving',
                'calories'=>300.0,
                'protein_grams'=>10.0,
                'carbs_grams'=>40.0,
                'fat_grams'=>10.0,
                'preparation_time_minutes'=>10,
                'sort_order'=>1,
        ]),
        ],
    );

    $action = app(StoreMealPlan::class);
    $mealPlan = $action->handle($user, $mealPlanData);

    expect($mealPlan->relationLoaded('meals'))->toBeTrue();
});
