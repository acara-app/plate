<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GroceryItemFactory;
use App\DataObjects\GroceryItemResponseData;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $grocery_list_id
 * @property-read string $name
 * @property-read string $quantity
 * @property-read string $category
 * @property-read bool $is_checked
 * @property-read array<int, int>|null $days
 * @property-read int $sort_order
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read GroceryList $groceryList
 */
final class GroceryItem extends Model
{
    /** @use HasFactory<GroceryItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<GroceryList, $this>
     */
    public function groceryList(): BelongsTo
    {
        return $this->belongsTo(GroceryList::class);
    }

    /**
     * Convert the item to a response DTO.
     */
    public function toResponseData(): GroceryItemResponseData
    {
        return new GroceryItemResponseData(
            id: $this->id,
            name: $this->name,
            quantity: $this->quantity,
            category: $this->category,
            is_checked: $this->is_checked,
            days: $this->days ?? [],
        );
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'grocery_list_id' => 'integer',
            'name' => 'string',
            'quantity' => 'string',
            'category' => 'string',
            'is_checked' => 'boolean',
            'days' => 'array',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
