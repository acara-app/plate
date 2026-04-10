<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\HealthAggregateCategory;
use App\Enums\HealthAggregationFunction;

final readonly class HealthMetricDescriptorData
{
    /**
     * @param  list<string>  $sourcePreference  Ordered list of preferred source substrings (e.g. ['Apple Watch', 'iPhone']).
     * @param  array<string, array{multiplier: float, offset?: float}>  $unitConversions  Map of foreign unit → affine conversion to canonical unit.
     */
    public function __construct(
        public string $identifier,
        public HealthAggregateCategory $category,
        public HealthAggregationFunction $function,
        public string $displayUnit,
        public string $canonicalUnit,
        public string $label,
        public array $sourcePreference = [],
        public array $unitConversions = [],
    ) {}

    public function isKnown(): bool
    {
        return $this->function !== HealthAggregationFunction::None;
    }

    public function isCumulative(): bool
    {
        return $this->category === HealthAggregateCategory::Cumulative;
    }

    public function isEvent(): bool
    {
        return $this->category === HealthAggregateCategory::Event;
    }

    /**
     * @return array{multiplier: float, offset: float}|null
     */
    public function conversionFrom(string $fromUnit): ?array
    {
        if ($fromUnit === $this->canonicalUnit) {
            return ['multiplier' => 1.0, 'offset' => 0.0];
        }

        $conv = $this->unitConversions[$fromUnit] ?? null;

        if ($conv === null) {
            return null;
        }

        return [
            'multiplier' => $conv['multiplier'],
            'offset' => $conv['offset'] ?? 0.0,
        ];
    }
}
