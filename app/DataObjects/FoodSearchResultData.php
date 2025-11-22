<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class FoodSearchResultData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $brand,
        public string $dataType,
    ) {}
}
