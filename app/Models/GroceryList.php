<?php

declare(strict_types=1);

namespace App\Models;

use App\DataObjects\GroceryItemResponseData;
use App\DataObjects\IngredientData;
use App\Enums\GroceryListStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read int $meal_plan_id
 * @property-read string $name
 * @property-read GroceryListStatus $status
 * @property-read array<string, mixed>|null $metadata
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 * @property-read MealPlan $mealPlan
 * @property-read Collection<int, GroceryItem> $items
 */
final class GroceryList extends Model
{
    /** @use HasFactory<\Database\Factories\GroceryListFactory> */
    use HasFactory;

    /**
     * Category order for sorting grocery items.
     *
     * @var array<int, string>
     */
    public const array CATEGORY_ORDER = [
        'Produce',
        'Meat & Seafood',
        'Dairy',
        'Bakery',
        'Pantry',
        'Frozen',
        'Beverages',
        'Condiments & Sauces',
        'Herbs & Spices',
        'Other',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<MealPlan, $this>
     */
    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }

    /**
     * @return HasMany<GroceryItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(GroceryItem::class)->orderBy('category')->orderBy('sort_order');
    }

    /**
     * Get items grouped by category, sorted by predefined order.
     *
     * @return Collection<string, Collection<int, GroceryItem>>
     */
    public function itemsByCategory(): Collection
    {
        $grouped = $this->items->groupBy('category');

        return $grouped->sortBy(function (Collection $items, string $category): int {
            $index = array_search($category, self::CATEGORY_ORDER, true);

            return $index === false ? count(self::CATEGORY_ORDER) : $index;
        });
    }

    /**
     * Get items grouped by category with response data format.
     *
     * @return Collection<string, array<int, GroceryItemResponseData>>
     */
    public function formattedItemsByCategory(): Collection
    {
        $this->deriveItemDaysIfMissing();

        return $this->itemsByCategory()->map(
            fn (Collection $items): array => $items->map(
                fn (GroceryItem $item): GroceryItemResponseData => $item->toResponseData()
            )->values()->all()
        );
    }

    /**
     * Get items grouped by day number.
     *
     * @return Collection<int, Collection<int, GroceryItem>>
     */
    public function itemsByDay(): Collection
    {
        $this->deriveItemDaysIfMissing();

        /** @var array<int, array<int, GroceryItem>> $byDay */
        $byDay = [];

        foreach ($this->items as $item) {
            $days = $item->days ?? [];
            foreach ($days as $day) {
                $byDay[$day][] = $item;
            }
        }

        ksort($byDay);

        return collect($byDay)->map(fn (array $items): Collection => collect($items));
    }

    /**
     * Get items grouped by day with response data format.
     *
     * @return Collection<int, array<int, GroceryItemResponseData>>
     */
    public function formattedItemsByDay(): Collection
    {
        return $this->itemsByDay()->map(
            fn (Collection $items): array => $items->map(
                fn (GroceryItem $item): GroceryItemResponseData => $item->toResponseData()
            )->values()->all()
        );
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'meal_plan_id' => 'integer',
            'name' => 'string',
            'status' => GroceryListStatus::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Derive days from meal plan ingredients for items missing day data.
     * This is a fallback for grocery lists generated before the days feature.
     */
    private function deriveItemDaysIfMissing(): void
    {
        $needsDerivation = $this->items->contains(fn (GroceryItem $item): bool => $item->days === null || $item->days === []);

        if (! $needsDerivation) {
            return;
        }

        $ingredientDayMap = $this->buildIngredientDayMap();

        foreach ($this->items as $item) {
            if ($item->days !== null && $item->days !== []) {
                continue;
            }

            $normalizedName = $this->normalizeIngredientName($item->name);
            $derivedDays = $ingredientDayMap[$normalizedName] ?? [];

            if ($derivedDays === []) {
                $derivedDays = $this->fuzzyMatchDays($item->name, $ingredientDayMap);
            }

            $item->update(['days' => array_values(array_unique($derivedDays))]);
        }
    }

    /**
     * Build a map of normalized ingredient names to day numbers.
     *
     * @return array<string, list<int>>
     */
    private function buildIngredientDayMap(): array
    {
        $this->mealPlan->load('meals');

        $map = [];

        foreach ($this->mealPlan->meals as $meal) {
            if ($meal->ingredients === null) {
                continue;
            }
            if (count($meal->ingredients) === 0) {
                continue;
            }
            foreach ($meal->ingredients as $ingredientArray) {
                $ingredient = IngredientData::from($ingredientArray);
                $name = $this->normalizeIngredientName($ingredient->name);

                if ($name === '') {
                    continue;
                }

                $map[$name][] = $meal->day_number;
            }
        }

        return array_map(
            fn (array $days): array => array_values(array_unique($days)),
            $map
        );
    }

    /**
     * Normalize an ingredient name for matching.
     */
    private function normalizeIngredientName(string $name): string
    {
        $name = mb_strtolower(mb_trim($name));
        $name = (string) preg_replace('/[^a-z0-9\s]/', '', $name);

        return (string) preg_replace('/\s+/', ' ', $name);
    }

    /**
     * Fuzzy match an item name against ingredient day map.
     *
     * @param  array<string, list<int>>  $ingredientDayMap
     * @return list<int>
     */
    private function fuzzyMatchDays(string $itemName, array $ingredientDayMap): array
    {
        $normalizedItem = $this->normalizeIngredientName($itemName);
        $itemWords = explode(' ', $normalizedItem);
        $matchedDays = [];

        foreach ($ingredientDayMap as $ingredientName => $days) {
            foreach ($itemWords as $word) {
                if (mb_strlen($word) >= 3 && str_contains($ingredientName, $word)) {
                    $matchedDays = array_merge($matchedDays, $days);
                    break;
                }
            }
        }

        return array_values(array_unique($matchedDays));
    }
}
