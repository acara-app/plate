<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateGroceryListAction;
use App\Enums\GroceryListStatus;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\MealPlan;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class GroceryListController
{
    public function __construct(
        #[CurrentUser] private \App\Models\User $user,
        private GenerateGroceryListAction $generateAction,
    ) {}

    /**
     * Display the grocery list for a meal plan.
     */
    public function show(MealPlan $mealPlan): Response
    {
        abort_if($mealPlan->user_id !== $this->user->id, 403);

        $groceryList = $mealPlan->groceryList;
        $needsGeneration = false;

        if (! $groceryList) {
            $groceryList = $this->generateAction->createPlaceholder($mealPlan);
            $needsGeneration = true;
        } elseif ($groceryList->status === GroceryListStatus::Generating) {
            // Still generating or never completed - check if items exist
            if ($groceryList->items()->count() === 0) {
                $needsGeneration = true;
            }
        } elseif ($groceryList->status === GroceryListStatus::Failed) {
            // Previous generation failed - retry
            $groceryList = $this->generateAction->createPlaceholder($mealPlan);
            $needsGeneration = true;
        }

        return Inertia::render('grocery-list/show', [
            'mealPlan' => [
                'id' => $mealPlan->id,
                'name' => $mealPlan->name,
                'duration_days' => $mealPlan->duration_days,
            ],
            'groceryList' => $needsGeneration
                ? Inertia::defer(fn (): array => $this->formatGroceryList(
                    $this->generateAction->generateItems($groceryList)
                ))
                : $this->formatGroceryList($groceryList),
        ]);
    }

    /**
     * Regenerate the grocery list for the meal plan.
     */
    public function store(MealPlan $mealPlan): Response
    {
        abort_if($mealPlan->user_id !== $this->user->id, 403);

        $groceryList = $this->generateAction->createPlaceholder($mealPlan);

        return Inertia::render('grocery-list/show', [
            'mealPlan' => [
                'id' => $mealPlan->id,
                'name' => $mealPlan->name,
                'duration_days' => $mealPlan->duration_days,
            ],
            'groceryList' => Inertia::defer(fn (): array => $this->formatGroceryList(
                $this->generateAction->generateItems($groceryList)
            )),
        ]);
    }

    /**
     * Toggle the checked status of a grocery item.
     */
    public function toggleItem(GroceryItem $groceryItem): RedirectResponse
    {
        $groceryList = $groceryItem->groceryList;

        abort_if($groceryList->user_id !== $this->user->id, 403);

        $groceryItem->update([
            'is_checked' => ! $groceryItem->is_checked,
        ]);

        $checkedCount = $groceryList->items()->where('is_checked', true)->count();
        $totalCount = $groceryList->items()->count();

        if ($checkedCount === $totalCount && $groceryList->status !== GroceryListStatus::Completed) {
            $groceryList->update(['status' => GroceryListStatus::Completed]);
        } elseif ($checkedCount < $totalCount && $groceryList->status === GroceryListStatus::Completed) {
            $groceryList->update(['status' => GroceryListStatus::Active]);
        }

        return back();
    }

    /**
     * Format grocery list data for the frontend.
     *
     * @return array<string, mixed>
     */
    private function formatGroceryList(GroceryList $groceryList): array
    {
        $groceryList->load('items', 'mealPlan.meals');

        return [
            'id' => $groceryList->id,
            'name' => $groceryList->name,
            'status' => $groceryList->status->value,
            'metadata' => $groceryList->metadata,
            'items_by_category' => $groceryList->formattedItemsByCategory(),
            'items_by_day' => $groceryList->formattedItemsByDay(),
            'total_items' => $groceryList->items->count(),
            'checked_items' => $groceryList->items->where('is_checked', true)->count(),
            'duration_days' => $groceryList->mealPlan->duration_days,
        ];
    }
}
