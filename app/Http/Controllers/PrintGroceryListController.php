<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class PrintGroceryListController
{
    public function __invoke(Request $request, MealPlan $mealPlan): View
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($mealPlan->user_id === $user->id, 403);

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
