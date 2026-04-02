<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MealPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class PrintGroceryListController
{
    public function __invoke(Request $request, MealPlan $mealPlan): View
    {
        Gate::authorize('view', $mealPlan);

        $groceryList = $mealPlan->groceryList;

        abort_unless($groceryList !== null, 404);

        $groceryList->load('items');

        return view('grocery-list.print', [
            'mealPlan' => $mealPlan,
            'groceryList' => $groceryList,
            'itemsByCategory' => $groceryList->itemsByCategory(),
        ]);
    }
}
