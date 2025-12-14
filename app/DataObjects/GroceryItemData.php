<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class GroceryItemData extends Data
{
    /**
     * @param  array<int, int>|null  $days
     */
    public function __construct(
        public string $name,
        public string $quantity,
        public string $category,
        public ?array $days = null,
    ) {}
}
