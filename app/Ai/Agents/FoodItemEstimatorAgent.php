<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Concerns\UsesConfiguredAgentModel;
use App\Ai\SystemPrompt;
use App\Contracts\Ai\UsesAgentModelProfiles;
use App\Data\FoodItemEstimateData;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;

#[MaxTokens(4000)]
#[Timeout(30)]
final class FoodItemEstimatorAgent implements Agent, HasProviderOptions, HasStructuredOutput, UsesAgentModelProfiles
{
    use Promptable;
    use UsesConfiguredAgentModel;

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are an expert nutritionist.',
                'You estimate the nutrition of one food item from its name and amount.',
                'Food names may be written in any language.',
            ],
            steps: [
                '1. Identify the food the name most likely refers to, including any preparation the name implies.',
                '2. Use the given weight in grams. If no weight is given, assume one typical single portion and report the grams you assumed.',
                '3. Estimate calories, protein, carbs, and fat for exactly that weight from standard nutrition reference values.',
            ],
            output: [
                'Return the structured response requested by the schema.',
                'Every value is for the full stated weight, not per 100 g.',
                'Calories are in kcal; protein, carbs, fat, and grams are in grams.',
                'Round every number to 1 decimal place.',
            ],
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'calories' => $schema->number()->required(),
            'protein' => $schema->number()->required(),
            'carbs' => $schema->number()->required(),
            'fat' => $schema->number()->required(),
            'grams' => $schema->number()->required(),
        ];
    }

    public function estimate(string $name, ?float $grams, ?string $portion, ?User $user = null): FoodItemEstimateData
    {
        $this->setModelUser($user);

        $prompt = sprintf(
            'Food: "%s". Weight: %s. Portion description: %s.',
            $name,
            $grams === null ? 'unknown' : $grams.' g',
            $portion === null || $portion === '' ? 'none' : '"'.$portion.'"',
        );

        /** @var StructuredAgentResponse $response */
        $response = $this->prompt($prompt);

        return FoodItemEstimateData::from($response->toArray());
    }
}
