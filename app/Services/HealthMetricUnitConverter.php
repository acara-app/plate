<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\HealthUnitConversionException;
use App\ValueObjects\HealthMetricDescriptorData;

final readonly class HealthMetricUnitConverter
{
    public function __construct(private HealthMetricRegistry $registry) {}

    /**
     * @return array{value: float, canonical_unit: string, original_unit: string}
     */
    public function toCanonical(string $typeIdentifier, float $value, ?string $unit): array
    {
        $fromUnit = $this->normalizeUnit($unit);
        $descriptor = $this->registry->fromIdentifier($typeIdentifier);

        if (! $descriptor instanceof HealthMetricDescriptorData || $descriptor->canonicalUnit === '') {
            return [
                'value' => $value,
                'canonical_unit' => $fromUnit,
                'original_unit' => $fromUnit,
            ];
        }

        if ($fromUnit === '') {
            // @codeCoverageIgnoreStart
            return [
                'value' => $value,
                'canonical_unit' => $descriptor->canonicalUnit,
                'original_unit' => $descriptor->canonicalUnit,
            ];
            // @codeCoverageIgnoreEnd
        }

        $conversion = $descriptor->conversionFrom($fromUnit);

        if ($conversion === null) {
            throw new HealthUnitConversionException(
                typeIdentifier: $typeIdentifier,
                fromUnit: $fromUnit,
                canonicalUnit: $descriptor->canonicalUnit,
            );
        }

        return [
            'value' => $value * $conversion['multiplier'] + $conversion['offset'],
            'canonical_unit' => $descriptor->canonicalUnit,
            'original_unit' => $fromUnit,
        ];
    }

    private function normalizeUnit(?string $unit): string
    {
        $trimmed = mb_trim($unit ?? '');

        return match ($trimmed) {
            'hrs', 'hr' => 'hours',
            default => $trimmed,
        };
    }
}
