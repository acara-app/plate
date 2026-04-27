<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class BrewPlanBlock extends Data
{
    /**
     * @param  array<string, mixed>  $props
     */
    public function __construct(
        public string $type,
        public array $props,
    ) {}
}
