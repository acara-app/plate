<?php

declare(strict_types=1);

namespace App\DataObjects\Memory;

use Spatie\LaravelData\Data;

/**
 * Result of memory validation/fact-checking.
 */
final class MemoryValidationResultData extends Data
{
    /**
     * @param  bool  $isValid  Whether the memory content is still valid/accurate.
     * @param  float  $confidence  Confidence level of the validation (0.0 to 1.0).
     * @param  string|null  $reason  Explanation for the validation result.
     * @param  string|null  $suggestedUpdate  If invalid, suggested corrected content.
     */
    public function __construct(
        public bool $isValid,
        public float $confidence,
        public ?string $reason = null,
        public ?string $suggestedUpdate = null,
    ) {}
}
