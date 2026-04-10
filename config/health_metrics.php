<?php

declare(strict_types=1);

use App\Enums\HealthAggregateCategory;
use App\Enums\HealthAggregationFunction;

/*
|--------------------------------------------------------------------------
| Health Metric Registry
|--------------------------------------------------------------------------
|
| The authoritative definition of every Apple HealthKit identifier the
| backend knows about. Each entry drives:
|
|   - How the daily aggregator summarises raw samples (category + function)
|   - The canonical storage unit + conversions from foreign units at ingest
|   - Source-preference ordering for interval-based dedup of cumulative data
|   - Human-readable labels for the dashboard
|
| Coverage:
|   - All 99 type identifiers sent by the iOS client
|     (see AcaraHealthSync/Models/HealthKitTypeIdentifier.swift)
|   - Backend-internal types (insulin, a1c, medicationDoseEvent,
|     bloodPressure) that flow through other ingestion paths.
|
| Semantics:
|   Cumulative    → sum over the day (stepCount, calories, nutrients, …)
|   Instantaneous → avg/min/max over point samples (heartRate, audio, gait)
|   SlowChanging  → latest measurement + weighted avg (weight, glucose, BP)
|   Event         → discrete occurrences counted + metadata preserved
|                   (medications, workouts, menstrual cycle events)
|   Sleep         → daily duration totals (legacy iOS 1.1.1 pre-aggregated;
|                   iOS 1.2+ uses raw sleep_sessions — see Phase 3)
|
| Bumping aggregation behaviour:
|   Increment HealthMetricRegistry::CURRENT_AGGREGATION_VERSION whenever
|   you change a category or function here, then run
|   `php artisan health:revalidate-aggregates --min-version=N` to rebuild
|   stored aggregates.
|
*/

$cumulativeSourcePref = ['Apple Watch', 'iPhone', 'Bluetooth Device'];

$cumulative = static fn (string $unit, string $label, ?string $canonical = null, array $conversions = [], array $sourcePref = []): array => [
    'category' => HealthAggregateCategory::Cumulative,
    'function' => HealthAggregationFunction::Sum,
    'display_unit' => $unit,
    'canonical_unit' => $canonical ?? $unit,
    'label' => $label,
    'source_preference' => $sourcePref,
    'unit_conversions' => $conversions,
];

$instantaneous = static fn (string $unit, string $label, ?string $canonical = null, array $conversions = []): array => [
    'category' => HealthAggregateCategory::Instantaneous,
    'function' => HealthAggregationFunction::Avg,
    'display_unit' => $unit,
    'canonical_unit' => $canonical ?? $unit,
    'label' => $label,
    'source_preference' => [],
    'unit_conversions' => $conversions,
];

$slow = static fn (string $unit, string $label, HealthAggregationFunction $fn = HealthAggregationFunction::Last, ?string $canonical = null, array $conversions = []): array => [
    'category' => HealthAggregateCategory::SlowChanging,
    'function' => $fn,
    'display_unit' => $unit,
    'canonical_unit' => $canonical ?? $unit,
    'label' => $label,
    'source_preference' => [],
    'unit_conversions' => $conversions,
];

$event = static fn (string $unit, string $label): array => [
    'category' => HealthAggregateCategory::Event,
    'function' => HealthAggregationFunction::Count,
    'display_unit' => $unit,
    'canonical_unit' => '',
    'label' => $label,
    'source_preference' => [],
    'unit_conversions' => [],
];

$sleep = static fn (string $label): array => [
    'category' => HealthAggregateCategory::Sleep,
    'function' => HealthAggregationFunction::Last,
    'display_unit' => 'hours',
    'canonical_unit' => 'hours',
    'label' => $label,
    'source_preference' => [],
    'unit_conversions' => [
        'min' => ['multiplier' => 1.0 / 60.0],
        'sec' => ['multiplier' => 1.0 / 3600.0],
    ],
];

// Reusable conversion tables.
$glucoseConversions = [
    'mmol/L' => ['multiplier' => 18.0182],
];

$kgConversions = [
    'lb' => ['multiplier' => 0.45359237],
    'g' => ['multiplier' => 0.001],
];

$kmConversions = [
    'mi' => ['multiplier' => 1.609344],
    'm' => ['multiplier' => 0.001],
];

$cmConversions = [
    'in' => ['multiplier' => 2.54],
    'ft' => ['multiplier' => 30.48],
    'm' => ['multiplier' => 100.0],
];

$celsiusConversions = [
    '°F' => ['multiplier' => 5.0 / 9.0, 'offset' => -32.0 * 5.0 / 9.0],
    'degF' => ['multiplier' => 5.0 / 9.0, 'offset' => -32.0 * 5.0 / 9.0],
    'K' => ['multiplier' => 1.0, 'offset' => -273.15],
];

$mLConversions = [
    'L' => ['multiplier' => 1000.0],
    'fl_oz_us' => ['multiplier' => 29.5735],
    'fl_oz' => ['multiplier' => 29.5735],
];

return [

    // ----------------------------------------------------------------
    // Glucose
    // ----------------------------------------------------------------
    'bloodGlucose' => $slow(
        unit: 'mg/dL',
        label: 'Blood Glucose',
        fn: HealthAggregationFunction::WeightedAvg,
        conversions: $glucoseConversions,
    ),

    // ----------------------------------------------------------------
    // Vitals
    // ----------------------------------------------------------------
    'heartRate' => $instantaneous('bpm', 'Heart Rate'),
    'heartRateVariability' => $instantaneous('ms', 'Heart Rate Variability'),
    'bloodPressureSystolic' => $slow(
        unit: 'mmHg',
        label: 'Blood Pressure (Systolic)',
        fn: HealthAggregationFunction::WeightedAvg,
    ),
    'bloodPressureDiastolic' => $slow(
        unit: 'mmHg',
        label: 'Blood Pressure (Diastolic)',
        fn: HealthAggregationFunction::WeightedAvg,
    ),
    'bloodOxygen' => $instantaneous('%', 'Blood Oxygen'),
    'restingHeartRate' => $slow('bpm', 'Resting Heart Rate'),
    'respiratoryRate' => $instantaneous('breaths/min', 'Respiratory Rate'),
    'wristTemperature' => $instantaneous('°C', 'Wrist Temperature', canonical: '°C', conversions: $celsiusConversions),

    // ----------------------------------------------------------------
    // Body
    // ----------------------------------------------------------------
    'weight' => $slow('kg', 'Weight', conversions: $kgConversions),
    'bodyMassIndex' => $slow('count', 'Body Mass Index'),
    'bodyFatPercentage' => $slow('%', 'Body Fat Percentage'),
    'leanBodyMass' => $slow('kg', 'Lean Body Mass', conversions: $kgConversions),
    'waistCircumference' => $slow('cm', 'Waist Circumference', conversions: $cmConversions),
    'height' => $slow('cm', 'Height', conversions: $cmConversions),

    // ----------------------------------------------------------------
    // Activity (Cumulative — Apple Watch preferred over iPhone)
    // ----------------------------------------------------------------
    'activeEnergy' => $cumulative('kcal', 'Active Energy', sourcePref: $cumulativeSourcePref),
    'basalEnergyBurned' => $cumulative('kcal', 'Basal Energy Burned', sourcePref: $cumulativeSourcePref),
    'exerciseMinutes' => $cumulative('min', 'Exercise Minutes', sourcePref: $cumulativeSourcePref),
    'walkingRunningDistance' => $cumulative('km', 'Walking + Running Distance', conversions: $kmConversions, sourcePref: $cumulativeSourcePref),
    'cyclingDistance' => $cumulative('km', 'Cycling Distance', conversions: $kmConversions, sourcePref: $cumulativeSourcePref),
    'swimmingDistance' => $cumulative('km', 'Swimming Distance', conversions: $kmConversions, sourcePref: $cumulativeSourcePref),
    'wheelchairDistance' => $cumulative('km', 'Wheelchair Distance', conversions: $kmConversions, sourcePref: $cumulativeSourcePref),
    'wheelchairPushCount' => $cumulative('count', 'Wheelchair Pushes', sourcePref: $cumulativeSourcePref),
    'flightsClimbed' => $cumulative('flights', 'Flights Climbed', sourcePref: $cumulativeSourcePref),
    'workouts' => $event('min', 'Workouts'),
    'stepCount' => $cumulative('steps', 'Steps', canonical: 'count', sourcePref: $cumulativeSourcePref),
    'standHours' => $cumulative('hours', 'Stand Hours', sourcePref: $cumulativeSourcePref),
    'standMinutes' => $cumulative('min', 'Stand Minutes', sourcePref: $cumulativeSourcePref),

    // ----------------------------------------------------------------
    // Mobility
    // ----------------------------------------------------------------
    'cardioFitness' => $slow('mL/kg·min', 'Cardio Fitness (VO₂ max)'),
    'walkingSpeed' => $instantaneous('m/s', 'Walking Speed'),
    'walkingHeartRateAverage' => $slow('bpm', 'Walking Heart Rate Average'),
    'walkingAsymmetry' => $instantaneous('%', 'Walking Asymmetry'),
    'walkingDoubleSupportPercentage' => $instantaneous('%', 'Walking Double Support %'),
    'walkingStepLength' => $instantaneous('cm', 'Walking Step Length', conversions: $cmConversions),
    'stairSpeedUp' => $instantaneous('m/s', 'Stair Speed (Up)'),
    'stairSpeedDown' => $instantaneous('m/s', 'Stair Speed (Down)'),
    'sixMinuteWalkTestDistance' => $slow('m', '6-Minute Walk Test Distance'),

    // ----------------------------------------------------------------
    // Sleep (legacy iOS 1.1.1 — daily pre-aggregated; iOS 1.2+ uses
    // sleep_sessions. See Phase 3 of the plan.)
    // ----------------------------------------------------------------
    'sleepAnalysis' => $sleep('Sleep'),
    'timeInBed' => $sleep('Time In Bed'),
    'timeAsleep' => $sleep('Time Asleep'),
    'remSleep' => $sleep('REM Sleep'),
    'coreSleep' => $sleep('Core Sleep'),
    'deepSleep' => $sleep('Deep Sleep'),
    'awakeTime' => $sleep('Awake Time'),

    // ----------------------------------------------------------------
    // Nutrition — every entry accumulates across the day (Sum).
    // Fixes B4: the old enum mis-classified Sugar/Calcium/Copper as
    // SlowChanging/Last, producing wrong totals.
    // ----------------------------------------------------------------
    'dietaryEnergy' => $cumulative('kcal', 'Calories'),
    'carbohydrates' => $cumulative('g', 'Carbohydrates'),
    'protein' => $cumulative('g', 'Protein'),
    'totalFat' => $cumulative('g', 'Total Fat'),
    'fiber' => $cumulative('g', 'Fiber'),
    'sugar' => $cumulative('g', 'Sugar'),
    'saturatedFat' => $cumulative('g', 'Saturated Fat'),
    'monounsaturatedFat' => $cumulative('g', 'Monounsaturated Fat'),
    'polyunsaturatedFat' => $cumulative('g', 'Polyunsaturated Fat'),
    'dietaryCholesterol' => $cumulative('mg', 'Cholesterol'),
    'sodium' => $cumulative('mg', 'Sodium'),
    'potassium' => $cumulative('mg', 'Potassium'),
    'calcium' => $cumulative('mg', 'Calcium'),
    'iron' => $cumulative('mg', 'Iron'),
    'zinc' => $cumulative('mg', 'Zinc'),
    'magnesium' => $cumulative('mg', 'Magnesium'),
    'phosphorus' => $cumulative('mg', 'Phosphorus'),
    'copper' => $cumulative('mg', 'Copper'),
    'manganese' => $cumulative('mg', 'Manganese'),
    'chloride' => $cumulative('mg', 'Chloride'),
    'vitaminA' => $cumulative('mcg', 'Vitamin A'),
    'vitaminC' => $cumulative('mg', 'Vitamin C'),
    'vitaminD' => $cumulative('mcg', 'Vitamin D'),
    'vitaminE' => $cumulative('mg', 'Vitamin E'),
    'vitaminK' => $cumulative('mcg', 'Vitamin K'),
    'vitaminB6' => $cumulative('mg', 'Vitamin B6'),
    'vitaminB12' => $cumulative('mcg', 'Vitamin B12'),
    'folate' => $cumulative('mcg', 'Folate'),
    'biotin' => $cumulative('mcg', 'Biotin'),
    'niacin' => $cumulative('mg', 'Niacin'),
    'pantothenicAcid' => $cumulative('mg', 'Pantothenic Acid'),
    'riboflavin' => $cumulative('mg', 'Riboflavin'),
    'thiamin' => $cumulative('mg', 'Thiamin'),
    'selenium' => $cumulative('mcg', 'Selenium'),
    'chromium' => $cumulative('mcg', 'Chromium'),
    'molybdenum' => $cumulative('mcg', 'Molybdenum'),
    'iodine' => $cumulative('mcg', 'Iodine'),
    'water' => $cumulative('mL', 'Water', conversions: $mLConversions),
    'caffeine' => $cumulative('mg', 'Caffeine'),

    // ----------------------------------------------------------------
    // Reproductive Health — discrete categorical events
    // ----------------------------------------------------------------
    'menstrualFlow' => $event('event', 'Menstrual Flow'),
    'ovulationTestResult' => $event('event', 'Ovulation Test'),
    'cervicalMucusQuality' => $event('event', 'Cervical Mucus Quality'),
    'basalBodyTemperature' => $instantaneous('°C', 'Basal Body Temperature', canonical: '°C', conversions: $celsiusConversions),
    'sexualActivity' => $event('event', 'Sexual Activity'),
    'intermenstrualBleeding' => $event('event', 'Intermenstrual Bleeding'),
    'contraceptive' => $event('event', 'Contraceptive'),
    'pregnancyTestResult' => $event('event', 'Pregnancy Test'),
    'progesteroneTestResult' => $event('event', 'Progesterone Test'),

    // ----------------------------------------------------------------
    // Hearing
    // ----------------------------------------------------------------
    'environmentalAudioExposure' => $instantaneous('dB', 'Environmental Audio Exposure'),
    'headphoneAudioLevels' => $instantaneous('dB', 'Headphone Audio Levels'),
    'audiogram' => $event('event', 'Audiogram'),

    // ----------------------------------------------------------------
    // Mindfulness & Mental Wellbeing
    // ----------------------------------------------------------------
    'mindfulMinutes' => $cumulative('min', 'Mindful Minutes'),
    'stateOfMind' => $event('event', 'State of Mind'),
    'timeInDaylight' => $cumulative('min', 'Time in Daylight'),

    // ----------------------------------------------------------------
    // Medications (iOS payload — 'medication' as a raw event)
    // ----------------------------------------------------------------
    'medication' => $event('dose', 'Medication'),

    // ----------------------------------------------------------------
    // Backend-internal types (not in iOS HealthKitTypeIdentifier, but
    // flow through chat/web/other ingest paths)
    // ----------------------------------------------------------------
    'insulin' => [
        'category' => HealthAggregateCategory::Event,
        'function' => HealthAggregationFunction::Sum,
        'display_unit' => 'IU',
        'canonical_unit' => 'IU',
        'label' => 'Insulin',
        'source_preference' => [],
        'unit_conversions' => [],
    ],
    'a1c' => $slow('%', 'A1C'),
    'medicationDoseEvent' => $event('dose', 'Medication Dose'),
    'bloodPressure' => $slow(
        unit: 'mmHg',
        label: 'Blood Pressure',
        fn: HealthAggregationFunction::WeightedAvg,
    ),
];
