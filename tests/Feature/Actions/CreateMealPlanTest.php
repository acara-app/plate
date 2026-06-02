<?php

declare(strict_types=1);

use App\Actions\CreateMealPlan;
use App\Enums\DietType;
use App\Enums\MealPlanGenerationStatus;
use App\Enums\MealPlanType;
use App\Models\User;
use App\Utilities\LanguageUtil;

covers(CreateMealPlan::class);

it('localizes meal plan name and description for the owners preferred locale', function (string $locale): void {
    $user = User::factory()->create(['locale' => $locale]);

    $mealPlan = resolve(CreateMealPlan::class)->handle($user, 7, DietType::Mediterranean);

    expect($mealPlan->name)
        ->toBe(__('common.meal_plans.name_with_diet', [
            'days' => 7,
            'diet' => __('common.meal_plans.diet_short.mediterranean', [], $locale),
        ], $locale))
        ->and($mealPlan->description)
        ->toBe(__('common.meal_plans.default_description', [], $locale));
})->with(LanguageUtil::keys());

it('uses the default plan name template when no diet type is provided', function (): void {
    $user = User::factory()->create(['locale' => 'en']);

    $mealPlan = resolve(CreateMealPlan::class)->handle($user, 5);

    expect($mealPlan->name)
        ->toBe(__('common.meal_plans.name_default', ['days' => 5], 'en'));
});

it('falls back to default locale when preferred language is unsupported', function (): void {
    $user = User::factory()->create(['locale' => 'xx']);

    $mealPlan = resolve(CreateMealPlan::class)->handle($user, 7, DietType::Keto);

    expect($mealPlan->name)
        ->toBe(__('common.meal_plans.name_with_diet', [
            'days' => 7,
            'diet' => __('common.meal_plans.diet_short.keto', [], LanguageUtil::default()),
        ], LanguageUtil::default()));
});

it('seeds generation metadata and resolves the plan type from the day count', function (): void {
    $user = User::factory()->create(['locale' => 'en']);

    $mealPlan = resolve(CreateMealPlan::class)->handle($user, 14, DietType::Balanced);

    expect($mealPlan->type)->toBe(MealPlanType::Monthly)
        ->and($mealPlan->duration_days)->toBe(14)
        ->and($mealPlan->metadata)
        ->toHaveKey('generated_at')
        ->generation_method->toBe('workflow')
        ->status->toBe(MealPlanGenerationStatus::Generating->value)
        ->days_completed->toBe(0)
        ->diet_type->toBe(DietType::Balanced->value);
});
