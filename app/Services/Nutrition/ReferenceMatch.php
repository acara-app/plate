<?php

declare(strict_types=1);

namespace App\Services\Nutrition;

use App\Models\ReferenceFood;

final readonly class ReferenceMatch
{
    public function __construct(
        public ReferenceFood $food,
        public float $score,
        public string $method,
    ) {}
}
