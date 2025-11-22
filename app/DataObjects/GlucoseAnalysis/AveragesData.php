<?php

declare(strict_types=1);

namespace App\DataObjects\GlucoseAnalysis;

use Spatie\LaravelData\Data;

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
