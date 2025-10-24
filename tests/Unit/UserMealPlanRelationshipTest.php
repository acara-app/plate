<?php

declare(strict_types=1);

use App\Models\MealPlan;
use App\Models\User;

it('has many meal plans', function (): void {
    $user = User::factory()->create();
    MealPlan::factory()->count(3)->for($user)->create();

    expect($user->mealPlans)->toHaveCount(3);
    expect($user->mealPlans->first())->toBeInstanceOf(MealPlan::class);
});

it('orders meal plans by latest first', function (): void {
    $user = User::factory()->create();

    $oldPlan = MealPlan::factory()->for($user)->create(['created_at' => now()->subDays(5)]);
    $newPlan = MealPlan::factory()->for($user)->create(['created_at' => now()]);

    $plans = $user->mealPlans;

    expect($plans->first()->id)->toBe($newPlan->id);
    expect($plans->last()->id)->toBe($oldPlan->id);
});

it('cascades delete meal plans when user is deleted', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    expect(MealPlan::find($mealPlan->id))->not->toBeNull();

    $user->delete();

    expect(MealPlan::find($mealPlan->id))->toBeNull();
});
