<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Agents\SpikePredictorAgent;
use App\Ai\Attributes\AiToolSensitivity;
use App\Contracts\Ai\PredictsGlucoseSpikes;
use App\Data\SpikePredictionData;
use App\Enums\DataSensitivity;
use App\Enums\SpikeRiskLevel;
use App\Models\User;
use App\Utilities\LanguageUtil;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::Sensitive)]
final readonly class PredictGlucoseSpike implements Tool
{
    public function name(): string
    {
        return 'predict_glucose_spike';
    }

    public function description(): string
    {
        return 'Predict the blood glucose spike impact of a specific food or meal. Returns estimated glucose increase, risk level, and personalized recommendations to minimize spikes. Use this when users ask about specific foods, restaurant meals, or want to understand glucose impact.';
    }

    public function handle(Request $request): string
    {
        /** @var string $food */
        $food = $request['food'] ?? '';
        /** @var string|null $context */
        $context = $request['context'] ?? null;

        if ($food === '') {
            return (string) json_encode([
                'error' => 'Food description is required',
                'prediction' => null,
            ]);
        }

        try {
            $predictor = resolve(PredictsGlucoseSpikes::class);

            $user = Auth::user();
            // @phpstan-ignore-next-line instanceof.alwaysTrue (test fakes bind a non-SpikePredictorAgent stub via app()->instance())
            if ($predictor instanceof SpikePredictorAgent && $user instanceof User) {
                ['label' => $language, 'code' => $languageCode] = LanguageUtil::resolve($user->locale);
                $predictor->withLanguage($language, $languageCode);
            }

            $prediction = $predictor->predict($food);

            return (string) json_encode([
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
        } catch (Exception $exception) {
            return (string) json_encode([
                'error' => 'Failed to predict glucose impact: '.$exception->getMessage(),
                'prediction' => null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'food' => $schema->string()
                ->description('Description of the food or meal to analyze (e.g., "Chipotle bowl with brown rice, chicken, and guacamole", "pizza slice", "oatmeal with berries")')
                ->required(),
            'context' => $schema->string()->required()->nullable()
                ->description('Additional context about the situation (e.g., "eating out at Chipotle", "pre-workout meal", "breakfast on the go")'),
        ];
    }

    private function estimateGlucoseIncrease(SpikeRiskLevel $riskLevel): int
    {
        return match ($riskLevel) {
            SpikeRiskLevel::Low => 20,
            SpikeRiskLevel::Medium => 45,
            SpikeRiskLevel::High => 80,
        };
    }

    /**
     * @return array<int, string>
     */
    private function generateRecommendations(SpikePredictionData $prediction, ?string $context): array
    {
        $recommendations = [];

        $recommendations[] = $prediction->smartFix;

        if ($context !== null && str_contains(mb_strtolower($context), 'chipotle')) {
            $recommendations[] = 'At Chipotle: Choose a bowl over a burrito (saves 300+ calories from the tortilla). Load up on fajita veggies and lettuce. Skip the corn salsa and go light on rice.';
        }

        $recommendations[] = match ($prediction->riskLevel) {
            SpikeRiskLevel::High => 'High spike risk: Consider eating protein first, adding healthy fats (avocado, nuts), or splitting this into two smaller portions.',
            SpikeRiskLevel::Medium => 'Moderate spike: Pair with a side salad or vegetables to add fiber and slow absorption.',
            SpikeRiskLevel::Low => 'Low spike risk: This is a good choice for stable glucose levels.',
        };

        return $recommendations;
    }
}
