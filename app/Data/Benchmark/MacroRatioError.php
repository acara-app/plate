<?php

declare(strict_types=1);

namespace App\Data\Benchmark;

use Spatie\LaravelData\Data;

final class MacroRatioError extends Data
{
    public function __construct(
        public ?float $carbsPp,
        public ?float $proteinPp,
        public ?float $fatPp,
    ) {}
}
