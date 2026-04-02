<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MealPlan;
use App\Models\User;

final class MealPlanPolicy
{
    public function view(User $user, MealPlan $mealPlan): bool
    {
        return $user->id === $mealPlan->user_id;
    }

    public function update(User $user, MealPlan $mealPlan): bool
    {
        return $user->id === $mealPlan->user_id;
    }
}
