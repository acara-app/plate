<?php

declare(strict_types=1);

namespace App\Data\Benchmark;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class HarnessReport extends Data
{
    /**
     * @param  DataCollection<int, PathMetrics>  $paths
     */
    public function __construct(
        public string $analyzerVersion,
        public bool $referenceLookupEnabled,
        public int $repeats,
        public int $skippedMeals,
        #[DataCollectionOf(PathMetrics::class)]
        public DataCollection $paths,
    ) {}
}
