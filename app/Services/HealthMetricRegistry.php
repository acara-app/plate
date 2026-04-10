<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\HealthAggregateCategory;
use App\Enums\HealthAggregationFunction;
use App\ValueObjects\HealthMetricDescriptorData;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class HealthMetricRegistry
{
    public const int CURRENT_AGGREGATION_VERSION = 1;

    /**
     * @var array<string, HealthMetricDescriptorData>|null
     */
    private ?array $descriptors = null;

    public function __construct(private readonly ConfigRepository $config) {}

    public function fromIdentifier(string $typeIdentifier): ?HealthMetricDescriptorData
    {
        return $this->hydrated()[$typeIdentifier] ?? null;
    }

    public function descriptorOrUnknown(string $typeIdentifier): HealthMetricDescriptorData
    {
        return $this->fromIdentifier($typeIdentifier) ?? new HealthMetricDescriptorData(
            identifier: $typeIdentifier,
            category: HealthAggregateCategory::Instantaneous,
            function: HealthAggregationFunction::None,
            displayUnit: '',
            canonicalUnit: '',
            label: $this->humanize($typeIdentifier),
        );
    }

    public function isKnown(string $typeIdentifier): bool
    {
        return isset($this->hydrated()[$typeIdentifier]);
    }

    /**
     * @return iterable<HealthMetricDescriptorData>
     */
    public function all(): iterable
    {
        // @codeCoverageIgnoreStart
        return array_values($this->hydrated());
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return list<string>
     */
    public function knownIdentifiers(): array
    {
        // @codeCoverageIgnoreStart
        return array_keys($this->hydrated());
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return array<string, HealthMetricDescriptorData>
     */
    private function hydrated(): array
    {
        if ($this->descriptors !== null) {
            return $this->descriptors;
        }

        /** @var array<string, array<string, mixed>> $raw */
        $raw = $this->config->get('health_metrics', []);

        $out = [];

        foreach ($raw as $identifier => $entry) {
            $category = $entry['category'] ?? null;
            $function = $entry['function'] ?? null;

            if (! $category instanceof HealthAggregateCategory || ! $function instanceof HealthAggregationFunction) {
                continue; // @codeCoverageIgnore
            }

            /** @var list<string> $sourcePref */
            $sourcePref = is_array($entry['source_preference'] ?? null) ? array_values($entry['source_preference']) : [];

            /** @var array<string, array{multiplier: float, offset?: float}> $conversions */
            $conversions = is_array($entry['unit_conversions'] ?? null) ? $entry['unit_conversions'] : [];

            /** @var string $displayUnit */
            $displayUnit = $entry['display_unit'] ?? '';
            /** @var string $canonicalUnit */
            $canonicalUnit = $entry['canonical_unit'] ?? $displayUnit;
            /** @var string $label */
            $label = $entry['label'] ?? $identifier;

            $out[$identifier] = new HealthMetricDescriptorData(
                identifier: $identifier,
                category: $category,
                function: $function,
                displayUnit: $displayUnit,
                canonicalUnit: $canonicalUnit,
                label: $label,
                sourcePreference: $sourcePref,
                unitConversions: $conversions,
            );
        }

        return $this->descriptors = $out;
    }

    private function humanize(string $identifier): string
    {
        $spaced = (string) preg_replace('/(?<!^)[A-Z]/', ' $0', $identifier);

        return ucfirst($spaced);
    }
}
