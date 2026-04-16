<?php

declare(strict_types=1);

namespace App\Data\Memory;

use Spatie\LaravelData\Data;

final class ExtractedMemoryData extends Data
{
    /**
     * @param  string  $content  Complete, self-contained memory statement.
     * @param  string  $memoryType  One of MemoryType enum values.
     * @param  array<int, string>  $categories  Assigned category labels.
     * @param  int  $importance  Priority score 1-10.
     * @param  string|null  $context  When/where/why this was learned.
     */
    public function __construct(
        public string $content,
        public string $memoryType,
        public array $categories,
        public int $importance,
        public ?string $context = null,
    ) {}
}
