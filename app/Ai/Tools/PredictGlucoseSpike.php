<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Agents\SpikePredictorAgent;
use App\Enums\SpikeRiskLevel;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class PredictGlucoseSpike implements Tool
{
    public function __construct(
        private SpikePredictorAgent $spikePredictor,
    ) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): string
    {
        return 'Predict the blood glucose spike impact of a specific food or meal. Returns estimated glucose increase, risk level, and personalized recommendations to minimize spikes. Use this when users ask about specific foods, restaurant meals, or want to understand glucose impact.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): string
    {
        $food = $request['food'] ?? '';
        $context = $request['context'] ?? null;

        if ($food === '') {
            return json_encode([
                'error' => 'Food description is required',
                'prediction' => null,
            ]);
        }

        try {
            $prediction = $this->spikePredictor->predict($food);

            return json_encode([
                'success' => true,
                'food' => $food,
                'prediction' => [
                    'risk_level' => $prediction->riskLevel->value,
                    'estimated_glucose_increase_mg_dl' => $this->estimateGlucoseIncrease($prediction->riskLevel),
                    'explanation' => $prediction->explanation,
                    'smart_fix' => $prediction->smartFix,
                    'spike_reduction_percentage' => $prediction->spikeReductionPercentage,
                ],
                'recommendations' => $this->generateRecommendations($prediction, $context),
            ]);
        } catch (Exception $e) {
            return json_encode([
                'error' => 'Failed to predict glucose impact: '.$e->getMessage(),
                'prediction' => null,
            ]);
        }
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'food' => $schema->string()
                ->description('Description of the food or meal to analyze (e.g., "Chipotle bowl with brown rice, chicken, and guacamole", "pizza slice", "oatmeal with berries")')
                ->required(),
            'context' => $schema->string()
                ->description('Additional context about the situation (e.g., "eating out at Chipotle", "pre-workout meal", "breakfast on the go")'),
        ];
    }

    /**
     * Estimate glucose increase in mg/dL based on risk level.
     */
    private function estimateGlucoseIncrease(SpikeRiskLevel $riskLevel): int
    {
        // Use deterministic values based on risk level instead of random
        return match ($riskLevel) {
            SpikeRiskLevel::Low => 20,
            SpikeRiskLevel::Medium => 45,
            SpikeRiskLevel::High => 80,
        };
    }

    /**
     * Generate personalized recommendations based on prediction and context.
     *
     * @return array<int, string>
     */
    private function generateRecommendations(\App\DataObjects\SpikePredictionData $prediction, ?string $context): array
    {
        $recommendations = [];

        // Add the smart fix as the primary recommendation
        $recommendations[] = $prediction->smartFix;

        // Add context-specific recommendations
        if ($context !== null && str_contains(mb_strtolower($context), 'chipotle')) {
            $recommendations[] = 'At Chipotle: Choose a bowl over a burrito (saves 300+ calories from the tortilla). Load up on fajita veggies and lettuce. Skip the corn salsa and go light on rice.';
        }

        // Add recommendations based on risk level
        $recommendations[] = match ($prediction->riskLevel) {
            SpikeRiskLevel::High => 'High spike risk: Consider eating protein first, adding healthy fats (avocado, nuts), or splitting this into two smaller portions.',
            SpikeRiskLevel::Medium => 'Moderate spike: Pair with a side salad or vegetables to add fiber and slow absorption.',
            SpikeRiskLevel::Low => 'Low spike risk: This is a good choice for stable glucose levels.',
        };

        return $recommendations;
    }
}
