<?php

declare(strict_types=1);

use App\Actions\StoreMealPlan;
use App\DataTransferObjects\MealData;
use App\DataTransferObjects\MealPlanData;
use App\Enums\MealPlanType;
use App\Enums\MealType;
use App\Models\User;

it('stores meal plan with preparation_notes in metadata', function (): void {
    $user = User::factory()->create();

    $mealPlanData = new MealPlanData(
        type: MealPlanType::Weekly,
        name: 'Test Plan with Prep Notes',
        description: 'A test meal plan with preparation notes',
        durationDays: 7,
        targetDailyCalories: 2000.0,
        macronutrientRatios: ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        meals: [
            new MealData(
                dayNumber: 1,
                type: MealType::Breakfast,
                name: 'Oatmeal',
                description: 'Healthy breakfast',
                preparationInstructions: 'Cook oats',
                ingredients: 'Oats, milk',
                portionSize: '1 bowl',
                calories: 350.0,
                proteinGrams: 10.0,
                carbsGrams: 60.0,
                fatGrams: 5.0,
                preparationTimeMinutes: 10,
                sortOrder: 1,
            ),
        ],
        metadata: [
            'preparation_notes' => 'Batch cook proteins on Sunday. Store in airtight containers. Use fresh vegetables within 3 days.',
        ],
    );

    $action = app(StoreMealPlan::class);
    $mealPlan = $action->handle($user, $mealPlanData);

    expect($mealPlan)
        ->metadata->toBe([
            'preparation_notes' => 'Batch cook proteins on Sunday. Store in airtight containers. Use fresh vegetables within 3 days.',
        ])
        ->metadata->toHaveKey('preparation_notes');

    expect($mealPlan->metadata['preparation_notes'])
        ->toBe('Batch cook proteins on Sunday. Store in airtight containers. Use fresh vegetables within 3 days.');
});

it('stores meal plan without preparation_notes when not provided', function (): void {
    $user = User::factory()->create();

    $mealPlanData = new MealPlanData(
        type: MealPlanType::Weekly,
        name: 'Test Plan',
        description: 'A test meal plan',
        durationDays: 7,
        targetDailyCalories: 2000.0,
        macronutrientRatios: ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        meals: [
            new MealData(
                dayNumber: 1,
                type: MealType::Breakfast,
                name: 'Oatmeal',
                description: 'Healthy breakfast',
                preparationInstructions: 'Cook oats',
                ingredients: 'Oats, milk',
                portionSize: '1 bowl',
                calories: 350.0,
                proteinGrams: 10.0,
                carbsGrams: 60.0,
                fatGrams: 5.0,
                preparationTimeMinutes: 10,
                sortOrder: 1,
            ),
        ],
        metadata: null,
    );

    $action = app(StoreMealPlan::class);
    $mealPlan = $action->handle($user, $mealPlanData);

    expect($mealPlan->metadata)->toBeNull();
});

it('stores meal plan with other metadata fields alongside preparation_notes', function (): void {
    $user = User::factory()->create();

    $mealPlanData = new MealPlanData(
        type: MealPlanType::Weekly,
        name: 'Test Plan',
        description: 'A test meal plan',
        durationDays: 7,
        targetDailyCalories: 2000.0,
        macronutrientRatios: ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        meals: [
            new MealData(
                dayNumber: 1,
                type: MealType::Breakfast,
                name: 'Oatmeal',
                description: 'Healthy breakfast',
                preparationInstructions: 'Cook oats',
                ingredients: 'Oats, milk',
                portionSize: '1 bowl',
                calories: 350.0,
                proteinGrams: 10.0,
                carbsGrams: 60.0,
                fatGrams: 5.0,
                preparationTimeMinutes: 10,
                sortOrder: 1,
            ),
        ],
        metadata: [
            'preparation_notes' => 'Weekly meal prep on Sundays',
            'bmi' => 22.5,
            'bmr' => 1600,
            'tdee' => 2000,
        ],
    );

    $action = app(StoreMealPlan::class);
    $mealPlan = $action->handle($user, $mealPlanData);

    expect($mealPlan->metadata)
        ->toHaveKey('preparation_notes')
        ->toHaveKey('bmi')
        ->toHaveKey('bmr')
        ->toHaveKey('tdee');

    expect($mealPlan->metadata['preparation_notes'])
        ->toBe('Weekly meal prep on Sundays');
});
