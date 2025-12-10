<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class GroceryItemResponseData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $quantity,
        public string $category,
        public bool $is_checked,
    ) {}
}
