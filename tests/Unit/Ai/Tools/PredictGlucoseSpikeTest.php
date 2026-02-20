<?php

declare(strict_types=1);

use App\Ai\Tools\PredictGlucoseSpike;
use App\Contracts\Ai\PredictsGlucoseSpikes;
use App\DataObjects\SpikePredictionData;
use App\Enums\SpikeRiskLevel;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

beforeEach(function (): void {
    // Create a simple test implementation
    $this->predictor = new class implements PredictsGlucoseSpikes
    {
        public ?SpikePredictionData $prediction = null;

        public ?Exception $exception = null;

        public function predict(string $food): SpikePredictionData
        {
            if ($this->exception instanceof Exception) {
                throw $this->exception;
            }

            return $this->prediction ?? new SpikePredictionData(
                food: $food,
                riskLevel: SpikeRiskLevel::Medium,
                estimatedGlycemicLoad: 30,
                explanation: 'Test explanation',
                smartFix: 'Test fix',
                spikeReductionPercentage: 15
            );
        }
    };

    app()->instance(PredictsGlucoseSpikes::class, $this->predictor);
    $this->tool = new PredictGlucoseSpike();
});

it('predicts glucose spike', function (): void {
    $this->predictor->prediction = new SpikePredictionData(
        food: 'pizza',
        riskLevel: SpikeRiskLevel::High,
        estimatedGlycemicLoad: 50,
        explanation: 'High refined carbs and fat.',
        smartFix: 'Eat salad first.',
        spikeReductionPercentage: 30
    );

    $request = new Request([
        'food' => 'pizza',
        'context' => null,
    ]);

    $result = $this->tool->handle($request);
    $data = json_decode((string) $result, true);

    expect($data['success'])->toBeTrue()
        ->and($data['food'])->toBe('pizza')
        ->and($data['prediction']['risk_level'])->toBe('high')
        ->and($data['prediction']['estimated_glucose_increase_mg_dl'])->toBe(80);
});

it('returns error if food is missing', function (): void {
    $request = new Request([]);

    $result = $this->tool->handle($request);
    $data = json_decode((string) $result, true);

    expect($data)->toHaveKey('error')
        ->and($data['error'])->toBe('Food description is required');
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('predict_glucose_spike')
        ->and($this->tool->description())->toContain('Predict the blood glucose spike');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;

    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['food', 'context']);
});

it('handles exceptions during prediction', function (): void {
    $this->predictor->exception = new Exception('API Error');

    $request = new Request(['food' => 'Unknown']);
    $result = $this->tool->handle($request);
    $data = json_decode((string) $result, true);

    expect($data)->toHaveKey('error')
        ->and($data['error'])->toContain('API Error');
});

it('provides specific recommendations for Chipotle context', function (): void {
    $this->predictor->prediction = new SpikePredictionData(
        food: 'Burrito',
        riskLevel: SpikeRiskLevel::High,
        estimatedGlycemicLoad: 50,
        explanation: 'High carbs',
        smartFix: 'Eat less',
        spikeReductionPercentage: 20
    );

    $request = new Request(['food' => 'Burrito', 'context' => 'Ordering at Chipotle']);
    $result = $this->tool->handle($request);
    $data = json_decode((string) $result, true);

    expect($data['recommendations'])->toContain('At Chipotle: Choose a bowl over a burrito (saves 300+ calories from the tortilla). Load up on fajita veggies and lettuce. Skip the corn salsa and go light on rice.');
});

it('calculates estimated glucose increase for low risk', function (): void {
    $this->predictor->prediction = new SpikePredictionData(
        food: 'Salad',
        riskLevel: SpikeRiskLevel::Low,
        estimatedGlycemicLoad: 10,
        explanation: 'Healthy',
        smartFix: 'None',
        spikeReductionPercentage: 0
    );

    $request = new Request(['food' => 'Salad']);
    $result = $this->tool->handle($request);
    $data = json_decode((string) $result, true);

    expect($data['prediction']['estimated_glucose_increase_mg_dl'])->toBe(20)
        ->and($data['recommendations'])->toContain('Low spike risk: This is a good choice for stable glucose levels.');
});

it('calculates estimated glucose increase for medium risk', function (): void {
    $this->predictor->prediction = new SpikePredictionData(
        food: 'Pasta',
        riskLevel: SpikeRiskLevel::Medium,
        estimatedGlycemicLoad: 30,
        explanation: 'Carbs',
        smartFix: 'Add protein',
        spikeReductionPercentage: 15
    );

    $request = new Request(['food' => 'Pasta']);
    $result = $this->tool->handle($request);
    $data = json_decode((string) $result, true);

    expect($data['prediction']['estimated_glucose_increase_mg_dl'])->toBe(45)
        ->and($data['recommendations'])->toContain('Moderate spike: Pair with a side salad or vegetables to add fiber and slow absorption.');
});
