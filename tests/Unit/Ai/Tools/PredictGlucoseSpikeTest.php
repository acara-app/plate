<?php

declare(strict_types=1);

namespace Tests\Unit\Ai\Tools;

use App\Ai\Agents\SpikePredictorAgent;
use App\Ai\Tools\PredictGlucoseSpike;
use App\DataObjects\SpikePredictionData;
use App\Enums\SpikeRiskLevel;
use Laravel\Ai\Tools\Request;
use Mockery;
use Tests\TestCase;

final class PredictGlucoseSpikeTest extends TestCase
{
    public function test_handle_predicts_glucose_spike(): void
    {
        $spikePredictor = Mockery::mock(SpikePredictorAgent::class);
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

        $this->assertTrue($data['success']);
        $this->assertEquals('pizza', $data['food']);
        $this->assertEquals('high', $data['prediction']['risk_level']);
        $this->assertEquals(80, $data['prediction']['estimated_glucose_increase_mg_dl']);
    }

    public function test_handle_returns_error_if_food_missing(): void
    {
        $spikePredictor = Mockery::mock(SpikePredictorAgent::class);
        $tool = new PredictGlucoseSpike($spikePredictor);

        $request = new Request([]);

        $result = $tool->handle($request);
        $data = json_decode($result, true);

        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Food description is required', $data['error']);
    }
}
