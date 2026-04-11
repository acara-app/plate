<?php

declare(strict_types=1);

namespace App\Data\GlucoseAnalysis;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class AveragesData extends Data
{
    public function __construct(
        public ?float $fasting,
        public ?float $beforeMeal,
        public ?float $postMeal,
        public ?float $random,
        public ?float $overall,
    ) {}
}
