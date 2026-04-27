<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class BrewPlanData extends Data
{
    /**
     * @param  DataCollection<int, BrewPlanBlock>  $blocks
     */
    public function __construct(
        public string $summary,
        #[DataCollectionOf(BrewPlanBlock::class)]
        public DataCollection $blocks,
    ) {}
}
