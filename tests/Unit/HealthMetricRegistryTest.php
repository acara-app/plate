<?php

declare(strict_types=1);

use App\Enums\HealthAggregateCategory;
use App\Enums\HealthAggregationFunction;
use App\Services\HealthMetricRegistry;
use App\ValueObjects\HealthMetricDescriptorData;

covers(HealthMetricRegistry::class);

beforeEach(function (): void {
    $this->registry = resolve(HealthMetricRegistry::class);
});

it('defines a descriptor for every identifier the iOS client sends', function (): void {
    $fixtureRaw = file_get_contents(base_path('tests/Fixtures/ios_health_metrics.json'));

    expect($fixtureRaw)->not->toBeFalse();

    /** @var array{type_count: int, categories: array<string, list<string>>} $fixture */
    $fixture = json_decode((string) $fixtureRaw, true);

    $expected = [];
    foreach ($fixture['categories'] as $identifiers) {
        foreach ($identifiers as $identifier) {
            $expected[] = $identifier;
        }
    }

    expect(count($expected))->toBe($fixture['type_count']);

    $missing = [];
    foreach ($expected as $identifier) {
        if (! $this->registry->isKnown($identifier)) {
            $missing[] = $identifier;
        }
    }

    expect($missing)->toBe([], sprintf(
        'Registry is missing %d iOS identifier(s): %s. Add them to config/health_metrics.php.',
        count($missing),
        implode(', ', $missing)
    ));
});

it('resolves all nutrients as cumulative sums (fixes B4 regression)', function (): void {
    $nutrients = [
        'dietaryEnergy', 'carbohydrates', 'protein', 'totalFat', 'fiber',
        'calcium', 'sodium', 'potassium', 'iron', 'zinc', 'magnesium',
        'phosphorus', 'copper', 'manganese', 'sugar', 'caffeine', 'water',
        'vitaminA', 'vitaminC', 'vitaminD', 'vitaminB6', 'vitaminB12',
        'vitaminE', 'vitaminK', 'folate', 'biotin', 'niacin', 'thiamin',
        'riboflavin', 'pantothenicAcid', 'selenium', 'chromium',
        'molybdenum', 'iodine', 'chloride', 'dietaryCholesterol',
        'saturatedFat', 'monounsaturatedFat', 'polyunsaturatedFat',
    ];

    foreach ($nutrients as $nutrient) {
        $descriptor = $this->registry->fromIdentifier($nutrient);

        expect($descriptor)->not->toBeNull('Missing registry entry for '.$nutrient);
        /** @var HealthMetricDescriptorData $descriptor */
        expect($descriptor->category)->toBe(HealthAggregateCategory::Cumulative, $nutrient.' should be Cumulative')
            ->and($descriptor->function)->toBe(HealthAggregationFunction::Sum, $nutrient.' should Sum daily');
    }
});

it('resolves blood glucose as slow-changing with weighted average and mmol/L conversion', function (): void {
    $descriptor = $this->registry->fromIdentifier('bloodGlucose');

    expect($descriptor)->not->toBeNull();
    /** @var HealthMetricDescriptorData $descriptor */
    expect($descriptor->category)->toBe(HealthAggregateCategory::SlowChanging)
        ->and($descriptor->function)->toBe(HealthAggregationFunction::WeightedAvg)
        ->and($descriptor->canonicalUnit)->toBe('mg/dL');

    $conversion = $descriptor->conversionFrom('mmol/L');
    expect($conversion)->not->toBeNull()
        ->and($conversion['multiplier'])->toBeGreaterThan(18.0)
        ->and($conversion['multiplier'])->toBeLessThan(18.1);
});

it('resolves cardioFitness (VO2 max) as slow-changing, not cumulative (fixes B4 regression)', function (): void {
    $descriptor = $this->registry->fromIdentifier('cardioFitness');

    expect($descriptor)->not->toBeNull();
    /** @var HealthMetricDescriptorData $descriptor */
    expect($descriptor->category)->toBe(HealthAggregateCategory::SlowChanging)
        ->and($descriptor->function)->toBe(HealthAggregationFunction::Last);
});

it('resolves body metrics (BMI, body fat, height) as slow-changing with Last function', function (): void {
    foreach (['bodyMassIndex', 'bodyFatPercentage', 'leanBodyMass', 'height'] as $metric) {
        $descriptor = $this->registry->fromIdentifier($metric);

        expect($descriptor)->not->toBeNull();
        /** @var HealthMetricDescriptorData $descriptor */
        expect($descriptor->category)->toBe(HealthAggregateCategory::SlowChanging, $metric.' should be SlowChanging')
            ->and($descriptor->function)->toBe(HealthAggregationFunction::Last, $metric.' should use Last');
    }
});

it('assigns Apple Watch > iPhone > Bluetooth Device priority only to cumulative activity metrics', function (): void {
    $cumulativeActivities = ['stepCount', 'activeEnergy', 'walkingRunningDistance', 'basalEnergyBurned', 'exerciseMinutes'];

    foreach ($cumulativeActivities as $metric) {
        $descriptor = $this->registry->fromIdentifier($metric);

        expect($descriptor)->not->toBeNull();
        /** @var HealthMetricDescriptorData $descriptor */
        expect($descriptor->sourcePreference)->toBe(['Apple Watch', 'iPhone', 'Bluetooth Device'], $metric.' source priority');
    }

    $nonCumulative = ['heartRate', 'bloodGlucose', 'weight'];
    foreach ($nonCumulative as $metric) {
        $descriptor = $this->registry->fromIdentifier($metric);

        /** @var HealthMetricDescriptorData $descriptor */
        expect($descriptor->sourcePreference)->toBe([], $metric.' should have no source priority');
    }
});

it('returns an unknown descriptor with function None for unrecognised identifiers', function (): void {
    $descriptor = $this->registry->descriptorOrUnknown('totallyMadeUpMetric');

    expect($descriptor->function)->toBe(HealthAggregationFunction::None)
        ->and($descriptor->isKnown())->toBeFalse();
});
