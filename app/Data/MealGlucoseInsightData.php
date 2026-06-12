<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\GlucoseUnit;
use App\Services\AiTransparency;
use Spatie\LaravelData\Data;

final class MealGlucoseInsightData extends Data
{
    public function __construct(
        public float $delta,
        public string $unit,
        public string $direction,
        public string $summary,
        public bool $overlapping,
        public string $notice,
        public ?string $comparable = null,
    ) {}

    public static function fromResponse(MealGlucoseResponseData $response, GlucoseUnit $unit, ?MealGlucosePatternData $pattern = null): self
    {
        $delta = $unit === GlucoseUnit::MmolL
            ? GlucoseUnit::mgDlToMmolL($response->delta)
            : round($response->delta, 0);

        $direction = match (true) {
            $response->delta > 0 => 'rose',
            $response->delta < 0 => 'fell',
            default => 'held steady',
        };

        $magnitude = $unit === GlucoseUnit::MmolL
            ? number_format(abs($delta), 1)
            : number_format(abs($delta), 0);

        $summary = match ($direction) {
            'rose' => sprintf('After this meal, your glucose rose %s %s in the hours afterward.', $magnitude, $unit->value),
            'fell' => sprintf('After this meal, your glucose fell %s %s in the hours afterward.', $magnitude, $unit->value),
            default => 'After this meal, your glucose held roughly steady in the hours afterward.',
        };

        if ($response->overlapping) {
            $summary .= ' Another meal overlapped this window, so it reflects more than this meal alone.';
        }

        return new self(
            delta: $delta,
            unit: $unit->value,
            direction: $direction,
            summary: $summary,
            overlapping: $response->overlapping,
            notice: AiTransparency::carbBoundaryNotice(),
            comparable: $pattern !== null ? self::comparableSummary($pattern, $unit) : null,
        );
    }

    private static function comparableSummary(MealGlucosePatternData $pattern, GlucoseUnit $unit): string
    {
        $decimals = $unit === GlucoseUnit::MmolL ? 1 : 0;

        $format = function (float $mgDl) use ($unit, $decimals): string {
            $value = $unit === GlucoseUnit::MmolL ? GlucoseUnit::mgDlToMmolL($mgDl) : round($mgDl, 0);

            return sprintf('%+.'.$decimals.'f', $value);
        };

        return sprintf(
            'Across %d similar meals (around %s g of carbs), your glucose has changed by a median of %s %s (range %s to %s).',
            $pattern->count,
            number_format($pattern->carbs, 0),
            $format($pattern->median),
            $unit->value,
            $format($pattern->min),
            $format($pattern->max),
        );
    }
}
