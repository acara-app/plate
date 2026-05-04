<?php

declare(strict_types=1);

namespace App\Data\Skills;

use Spatie\LaravelData\Data;

/**
 * @codeCoverageIgnore
 */
final class SkillSummary extends Data
{
    public function __construct(
        public string $name,
        public string $description,
    ) {}
}
