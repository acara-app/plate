<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * @codeCoverageIgnore
 */
final class CaffeineLimitData extends Data
{
    /**
     * @param  array<int, string>  $reasons
     * @param  array<int, string>  $conditions
     */
    public function __construct(
        public int $heightCm,
        public float $weightKg,
        public int $age,
        public string $sex,
        public string $sensitivity,
        public string $sensitivityLabel,
        public ?int $limitMg,
        public string $status,
        public bool $hasCautionContext,
        public ?string $contextLabel,
        public array $reasons,
        public string $sourceSummary,
        public string $formulaUsed,
        public array $conditions,
    ) {}
}
