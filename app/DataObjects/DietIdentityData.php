<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class DietIdentityData extends Data
{
    public function __construct(
        public readonly string $goal_choice,
        public readonly string $animal_product_choice,
        public readonly string $intensity_choice,
    ) {}
}
