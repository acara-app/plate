<?php

declare(strict_types=1);

use App\DataObjects\SpikePredictionData;
use App\Enums\SpikeRiskLevel;

it('can be created from array using from method', function (): void {
    $data = [
        'food' => '2 slices of pepperoni pizza',
        'riskLevel' => 'high',
        'estimatedGlycemicLoad' => 45,
        'explanation' => 'Pizza has refined carbs and high glycemic index.',
        'smartFix' => 'Add a side salad with olive oil dressing before eating.',
        'spikeReductionPercentage' => 30,
    ];

    $predictionData = SpikePredictionData::from($data);

    expect($predictionData)
        ->food->toBe('2 slices of pepperoni pizza')
        ->riskLevel->toBe(SpikeRiskLevel::High)
        ->estimatedGlycemicLoad->toBe(45)
        ->explanation->toBe('Pizza has refined carbs and high glycemic index.')
        ->smartFix->toBe('Add a side salad with olive oil dressing before eating.')
        ->spikeReductionPercentage->toBe(30);
});

it('can be created directly with constructor', function (): void {
    $predictionData = new SpikePredictionData(
        food: 'Greek yogurt with berries',
        riskLevel: SpikeRiskLevel::Low,
        estimatedGlycemicLoad: 12,
        explanation: 'Greek yogurt has protein and healthy fats that slow digestion.',
        smartFix: 'Add some nuts for extra fiber and healthy fats.',
        spikeReductionPercentage: 15,
    );

    expect($predictionData)
        ->food->toBe('Greek yogurt with berries')
        ->riskLevel->toBe(SpikeRiskLevel::Low)
        ->estimatedGlycemicLoad->toBe(12)
        ->explanation->toBe('Greek yogurt has protein and healthy fats that slow digestion.')
        ->smartFix->toBe('Add some nuts for extra fiber and healthy fats.')
        ->spikeReductionPercentage->toBe(15);
});

it('can be converted to array', function (): void {
    $predictionData = new SpikePredictionData(
        food: 'White bread',
        riskLevel: SpikeRiskLevel::Medium,
        estimatedGlycemicLoad: 28,
        explanation: 'White bread is a refined carb with moderate glycemic load.',
        smartFix: 'Switch to whole grain bread for more fiber.',
        spikeReductionPercentage: 25,
    );

    $array = $predictionData->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKeys(['food', 'risk_level', 'estimated_glycemic_load', 'explanation', 'smart_fix', 'spike_reduction_percentage'])
        ->and($array['food'])->toBe('White bread')
        ->and($array['risk_level'])->toBe('medium')
        ->and($array['estimated_glycemic_load'])->toBe(28);
});

it('accepts all spike risk levels', function (SpikeRiskLevel $level): void {
    $predictionData = new SpikePredictionData(
        food: 'Test food',
        riskLevel: $level,
        estimatedGlycemicLoad: 20,
        explanation: 'Test explanation',
        smartFix: 'Test smart fix',
        spikeReductionPercentage: 10,
    );

    expect($predictionData->riskLevel)->toBe($level);
})->with([
    'low' => SpikeRiskLevel::Low,
    'medium' => SpikeRiskLevel::Medium,
    'high' => SpikeRiskLevel::High,
]);
