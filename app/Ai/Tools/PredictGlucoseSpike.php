<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Agents\SpikePredictorAgent;
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
                    'estimated_glucose_increase_mg_dl' => $this->estimateGlucoseIncrease($prediction->estimatedGlycemicLoad),
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
     * Estimate glucose increase in mg/dL based on glycemic load.
     */
    private function estimateGlucoseIncrease(int $glycemicLoad): int
    {
        // Rough estimation: GL 0-10 = +10-30 mg/dL, GL 11-20 = +30-60 mg/dL, GL 20+ = +60-100+ mg/dL
        return match (true) {
            $glycemicLoad <= 10 => random_int(10, 30),
            $glycemicLoad <= 20 => random_int(30, 60),
            default => random_int(60, 100),
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
        if ($prediction->riskLevel->value === 'high') {
            $recommendations[] = 'High spike risk: Consider eating protein first, adding healthy fats (avocado, nuts), or splitting this into two smaller portions.';
        } elseif ($prediction->riskLevel->value === 'medium') {
            $recommendations[] = 'Moderate spike: Pair with a side salad or vegetables to add fiber and slow absorption.';
        }

        return $recommendations;
    }
}
