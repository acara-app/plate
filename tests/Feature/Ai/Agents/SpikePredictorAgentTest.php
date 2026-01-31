<?php

declare(strict_types=1);

use App\Ai\Agents\SpikePredictorAgent;
use App\DataObjects\SpikePredictionData;
use App\Enums\SpikeRiskLevel;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;

it('uses gemini provider', function (): void {
    $agent = new SpikePredictorAgent;

    expect($agent->provider())->toBe(Provider::OpenAI);
});

it('uses gemini-2.5-flash model', function (): void {
    $agent = new SpikePredictorAgent;

    expect($agent->model())->toBe('gpt-5-mini');
});

it('has proper max tokens', function (): void {
    $agent = new SpikePredictorAgent;

    expect($agent->maxTokens())->toBe(2000);
});

it('has client options with timeout', function (): void {
    $agent = new SpikePredictorAgent;

    expect($agent->clientOptions())->toBe(['timeout' => 60]);
});

it('system prompt contains glycemic analysis instructions', function (): void {
    $agent = new SpikePredictorAgent;
    $systemPrompt = $agent->systemPrompt();

    expect($systemPrompt)
        ->toContain('glycemic index')
        ->toContain('spike risk')
        ->toContain('smart_fix');
});

it('predicts high spike risk for high glycemic foods', function (): void {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"risk_level": "high", "estimated_gl": 45, "explanation": "White bread is a refined carbohydrate with high glycemic index.", "smart_fix": "Add avocado or peanut butter to slow glucose absorption.", "spike_reduction_percentage": 30}'),
    ]);

    $agent = new SpikePredictorAgent;
    $result = $agent->predict('2 slices of white bread');

    expect($result)
        ->toBeInstanceOf(SpikePredictionData::class)
        ->food->toBe('2 slices of white bread')
        ->riskLevel->toBe(SpikeRiskLevel::High)
        ->estimatedGlycemicLoad->toBe(45)
        ->explanation->toContain('refined carbohydrate')
        ->smartFix->toContain('avocado')
        ->spikeReductionPercentage->toBe(30);
});

it('predicts low spike risk for low glycemic foods', function (): void {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"risk_level": "low", "estimated_gl": 8, "explanation": "Almonds are high in protein, healthy fats, and fiber with minimal carbohydrates.", "smart_fix": "Pair with a small piece of dark chocolate for a satisfying snack.", "spike_reduction_percentage": 10}'),
    ]);

    $agent = new SpikePredictorAgent;
    $result = $agent->predict('handful of almonds');

    expect($result)
        ->toBeInstanceOf(SpikePredictionData::class)
        ->food->toBe('handful of almonds')
        ->riskLevel->toBe(SpikeRiskLevel::Low)
        ->estimatedGlycemicLoad->toBe(8)
        ->explanation->toContain('fiber')
        ->spikeReductionPercentage->toBe(10);
});

it('predicts medium spike risk for moderate glycemic foods', function (): void {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"risk_level": "medium", "estimated_gl": 28, "explanation": "Brown rice has moderate glycemic index but fiber helps slow absorption.", "smart_fix": "Add grilled chicken or tofu for protein to further reduce spike.", "spike_reduction_percentage": 25}'),
    ]);

    $agent = new SpikePredictorAgent;
    $result = $agent->predict('bowl of brown rice');

    expect($result)
        ->toBeInstanceOf(SpikePredictionData::class)
        ->food->toBe('bowl of brown rice')
        ->riskLevel->toBe(SpikeRiskLevel::Medium)
        ->estimatedGlycemicLoad->toBe(28);
});

it('handles json response with markdown code blocks', function (): void {
    Prism::fake([
        TextResponseFake::make()
            ->withText('```json
{"risk_level": "high", "estimated_gl": 50, "explanation": "Sugary soda causes rapid glucose spike.", "smart_fix": "Switch to sparkling water with lemon.", "spike_reduction_percentage": 35}
```'),
    ]);

    $agent = new SpikePredictorAgent;
    $result = $agent->predict('can of soda');

    expect($result)
        ->toBeInstanceOf(SpikePredictionData::class)
        ->riskLevel->toBe(SpikeRiskLevel::High);
});

it('throws exception for invalid json response', function (): void {
    Prism::fake([
        TextResponseFake::make()
            ->withText('This is not valid JSON'),
    ]);

    $agent = new SpikePredictorAgent;
    $agent->predict('some food');
})->throws(InvalidArgumentException::class);
