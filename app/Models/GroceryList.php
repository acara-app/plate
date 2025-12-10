<?php

declare(strict_types=1);

namespace App\Models;

use App\DataObjects\GroceryItemResponseData;
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
        return $this->itemsByCategory()->map(
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
}
