<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\MealPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

final class PrintMealPlanController
{
    public function __invoke(Request $request, MealPlan $mealPlan): View
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($mealPlan->user_id === $user->id, 403);

        $mealPlan->load(['meals' => function (HasMany $query): void {
            $query->orderBy('day_number')->orderBy('sort_order');
        }]);

        $mealsByDay = $mealPlan->meals->groupBy('day_number');

        return view('meal-plans.print', [
            'mealPlan' => $mealPlan,
            'mealsByDay' => $mealsByDay,
        ]);
    }
}
