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

    private ?string $language = null;

    private ?string $languageCode = null;

    public function withLanguage(string $language, string $languageCode): self
    {
        $this->language = $language;
        $this->languageCode = $languageCode;

        return $this;
    }

    public function instructions(): string
    {
        $output = [
            'Return the structured response requested by the schema.',
            'Every value is for the full stated weight, not per 100 g.',
            'Calories are in kcal; protein, carbs, fat, fiber, and grams are in grams.',
            'carbs is total carbohydrate including dietary fiber; fiber is the total dietary fiber.',
            'Round every number to 1 decimal place.',
            '`portion` describes only this food: the weight in grams, followed by a short household measure for that amount in parentheses. Keep it under 60 characters and never mention any other food.',
        ];

        if ($this->language !== null && $this->languageCode !== null) {
            $output[] = sprintf(
                'Write `portion` in %s (language code: `%s`). Keep numbers as numerals and the gram unit as "g". Use natural, idiomatic terms in %s.',
                $this->language,
                $this->languageCode,
                $this->language,
            );
        }

        return (string) new SystemPrompt(
            background: [
                'You are an expert nutritionist.',
                'You estimate the nutrition of one food item from its name and amount.',
                'Food names may be written in any language.',
            ],
            steps: [
                '1. Identify the food the name most likely refers to, including any preparation the name implies.',
                '2. Use the given weight in grams. If no weight is given, assume one typical single portion and report the grams you assumed.',
                '3. Estimate calories, protein, carbs, fat, and dietary fiber for exactly that weight from standard nutrition reference values.',
                '4. Describe that amount of this food as `portion`.',
            ],
            output: $output,
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
            'fiber' => $schema->number()->required(),
            'grams' => $schema->number()->required(),
            'portion' => $schema->string()->required(),
        ];
    }

    public function estimate(string $name, ?float $grams, ?User $user = null): FoodItemEstimateData
    {
        $this->setModelUser($user);

        $prompt = sprintf(
            'Food: "%s". Weight: %s.',
            $name,
            $grams === null ? 'unknown' : $grams.' g',
        );

        /** @var StructuredAgentResponse $response */
        $response = $this->prompt($prompt);

        return FoodItemEstimateData::from($response->toArray());
    }
}
