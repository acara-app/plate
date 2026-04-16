<?php

declare(strict_types=1);

namespace App\Ai\Agents\Memory;

use App\Ai\Agents\Memory\Concerns\UsesMemoryAgentConfig;
use App\Ai\SystemPrompt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;

#[MaxTokens(1024)]
#[Timeout(60)]
final class MemoryCategorizerAgent implements Agent, HasStructuredOutput
{
    use Promptable;
    use UsesMemoryAgentConfig;

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are a concise memory categorization assistant.',
                'You receive a natural-language memory statement and assign a small set of semantic categories.',
            ],
            steps: [
                '1. Read the memory content.',
                '2. Choose 1-4 short, lowercase, snake_case category labels (e.g. "preference", "dietary_restriction", "goal").',
                '3. Prefer common categories: preference, goal, fact, habit, relationship, skill, context, event, health, nutrition, schedule, professional, personal.',
            ],
            output: [
                'Return only the categories array.',
                'No explanations, no other fields.',
            ],
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'categories' => (new ArrayType)
                ->items($schema->string())
                ->description('1-4 short lowercase snake_case category labels.'),
        ];
    }

    /**
     * @return array<int, string>
     *
     * @codeCoverageIgnore
     */
    public function categorize(string $content): array
    {
        /** @var StructuredAgentResponse $response */
        $response = $this->prompt("Categorize this memory:\n\n".$content);

        /** @var array{categories?: array<int, string>} $data */
        $data = $response->toArray();

        return array_values(array_filter($data['categories'] ?? [], is_string(...)));
    }
}
