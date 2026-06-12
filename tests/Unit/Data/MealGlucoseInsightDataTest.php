<?php

declare(strict_types=1);

use App\Data\MealGlucoseInsightData;
use App\Data\MealGlucosePatternData;
use App\Data\MealGlucoseResponseData;
use App\Enums\GlucoseUnit;
use App\Services\AiTransparency;

function glucoseResponseFixture(float $delta, bool $overlapping = false): MealGlucoseResponseData
{
    return new MealGlucoseResponseData(
        baseline: 100.0,
        peak: 100.0 + $delta,
        delta: $delta,
        readingsInWindow: 2,
        overlapping: $overlapping,
    );
}

it('formats a glucose rise in mg/dL with observational, non-causal wording', function (): void {
    $insight = MealGlucoseInsightData::fromResponse(glucoseResponseFixture(42), GlucoseUnit::MgDl);

    expect($insight->direction)->toBe('rose')
        ->and($insight->delta)->toBe(42.0)
        ->and($insight->unit)->toBe('mg/dL')
        ->and($insight->summary)->toContain('After this meal, your glucose rose 42 mg/dL')
        ->and($insight->summary)->not->toContain('because')
        ->and($insight->notice)->toBe(AiTransparency::carbBoundaryNotice());
});

it('converts the delta to the mmol/L preference', function (): void {
    $insight = MealGlucoseInsightData::fromResponse(glucoseResponseFixture(-36), GlucoseUnit::MmolL);

    expect($insight->direction)->toBe('fell')
        ->and($insight->delta)->toBe(-2.0)
        ->and($insight->summary)->toContain('fell 2.0 mmol/L');
});

it('describes a flat response as steady without a number', function (): void {
    $insight = MealGlucoseInsightData::fromResponse(glucoseResponseFixture(0), GlucoseUnit::MgDl);

    expect($insight->direction)->toBe('held steady')
        ->and($insight->summary)->toContain('held roughly steady')
        ->and($insight->summary)->not->toContain('0 mg/dL');
});

it('appends an honest caveat when another meal overlapped the window', function (): void {
    $insight = MealGlucoseInsightData::fromResponse(glucoseResponseFixture(50, overlapping: true), GlucoseUnit::MgDl);

    expect($insight->overlapping)->toBeTrue()
        ->and($insight->summary)->toContain('Another meal overlapped this window');
});

it('includes a signed comparable-meals summary when a pattern is provided', function (): void {
    $pattern = new MealGlucosePatternData(carbs: 40, median: 40, min: 30, max: 55, count: 4);

    $insight = MealGlucoseInsightData::fromResponse(glucoseResponseFixture(42), GlucoseUnit::MgDl, $pattern);

    expect($insight->comparable)
        ->toContain('Across 4 similar meals')
        ->toContain('around 40 g of carbs')
        ->toContain('+40 mg/dL')
        ->toContain('+30')
        ->toContain('+55');
});

it('converts the comparable summary to mmol/L', function (): void {
    $pattern = new MealGlucosePatternData(carbs: 40, median: 36, min: 18, max: 54, count: 5);

    $insight = MealGlucoseInsightData::fromResponse(glucoseResponseFixture(36), GlucoseUnit::MmolL, $pattern);

    expect($insight->comparable)
        ->toContain('+2.0 mmol/L')
        ->toContain('Across 5 similar meals');
});

it('omits the comparable summary when no pattern is provided', function (): void {
    expect(MealGlucoseInsightData::fromResponse(glucoseResponseFixture(42), GlucoseUnit::MgDl)->comparable)
        ->toBeNull();
});
