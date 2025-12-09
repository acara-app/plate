<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\GroceryListGeneratorAgent;
use App\Enums\GroceryListStatus;
use App\Models\GroceryList;
use App\Models\MealPlan;
use Throwable;

final readonly class GenerateGroceryListAction
{
    public function __construct(
        private GroceryListGeneratorAgent $agent,
    ) {}

    public function createPlaceholder(MealPlan $mealPlan): GroceryList
    {
        $mealPlan->groceryList()->delete();

        return $mealPlan->groceryList()->create([
            'user_id' => $mealPlan->user_id,
            'name' => "Grocery List for {$mealPlan->name}",
            'status' => GroceryListStatus::Generating,
            'metadata' => [
                'started_at' => now()->toIso8601String(),
                'meal_plan_duration_days' => $mealPlan->duration_days,
            ],
        ]);
    }

    /**
     * Generate items for an existing grocery list using AI.
     */
    public function generateItems(GroceryList $groceryList): GroceryList
    {
        $mealPlan = $groceryList->mealPlan;

        try {
            $groceryListData = $this->agent->generate($mealPlan);

            $sortOrder = 0;
            foreach ($groceryListData->items as $item) {
                $groceryList->items()->create([
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'category' => $item->category,
                    'is_checked' => false,
                    'sort_order' => $sortOrder++,
                ]);
            }

            $groceryList->update([
                'status' => GroceryListStatus::Active,
                'metadata' => array_merge($groceryList->metadata ?? [], [
                    'completed_at' => now()->toIso8601String(),
                    'total_items' => $groceryListData->items->count(),
                ]),
            ]);
        } catch (Throwable $e) {
            $groceryList->update([
                'status' => GroceryListStatus::Failed,
                'metadata' => array_merge($groceryList->metadata ?? [], [
                    'failed_at' => now()->toIso8601String(),
                    'error' => $e->getMessage(),
                ]),
            ]);
        }

        return $groceryList->fresh(['items']);
    }

    /**
     * Full generation: create placeholder and generate items.
     */
    public function handle(MealPlan $mealPlan): GroceryList
    {
        $groceryList = $this->createPlaceholder($mealPlan);

        return $this->generateItems($groceryList);
    }
}
