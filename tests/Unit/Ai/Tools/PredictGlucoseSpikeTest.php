<?php

declare(strict_types=1);

use App\Ai\Contracts\PredictsGlucoseSpikes;
use App\Ai\Tools\PredictGlucoseSpike;
use App\DataObjects\SpikePredictionData;
use App\Enums\SpikeRiskLevel;
use Laravel\Ai\Tools\Request;

it('predicts glucose spike', function (): void {
    $spikePredictor = mock(PredictsGlucoseSpikes::class);
    $spikePredictor->shouldReceive('predict')
        ->once()
        ->with('pizza')
        ->andReturn(new SpikePredictionData(
            'pizza',
            SpikeRiskLevel::High,
            50,
            'High refined carbs and fat.',
            'Eat salad first.',
            30
        ));

    $tool = new PredictGlucoseSpike($spikePredictor);

    $request = new Request([
        'food' => 'pizza',
        'context' => null,
    ]);

    $result = $tool->handle($request);
    $data = json_decode($result, true);

    expect($data['success'])->toBeTrue()
        ->and($data['food'])->toBe('pizza')
        ->and($data['prediction']['risk_level'])->toBe('high')
        ->and($data['prediction']['estimated_glucose_increase_mg_dl'])->toBe(80);
});

it('returns error if food is missing', function (): void {
    $spikePredictor = mock(PredictsGlucoseSpikes::class);
    $tool = new PredictGlucoseSpike($spikePredictor);

    $request = new Request([]);

    $result = $tool->handle($request);
    $data = json_decode($result, true);

    expect($data)->toHaveKey('error')
        ->and($data['error'])->toBe('Food description is required');
});
