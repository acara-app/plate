<?php

declare(strict_types=1);

namespace App\Ai\Agents\Memory;

use App\Ai\Agents\Memory\Concerns\UsesMemoryAgentConfig;
use App\Ai\SystemPrompt;
use App\Contracts\Ai\Memory\GeneratesMemoryQueries;
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
final class MemoryQueryGeneratorAgent implements Agent, GeneratesMemoryQueries, HasStructuredOutput
{
    use Promptable;
    use UsesMemoryAgentConfig;

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are a search-query planner for a long-term semantic memory store.',
                'Given the last few turns of a conversation plus the current user message, you produce 2-4 concise semantic search queries that will retrieve relevant past memories.',
            ],
            steps: [
                '1. Identify the topics, entities, preferences, goals, or events implied by the latest user message.',
                '2. For each, draft a short retrieval query (a noun phrase or question fragment, not a full sentence).',
                '3. Prefer multiple angles over a single vague query (e.g. "dairy allergy", "lactose intolerance symptoms") instead of "health stuff".',
            ],
            output: [
                'Return only the queries array.',
                'Between 2 and 4 queries. Each under ~60 characters.',
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
            'queries' => (new ArrayType)
                ->items($schema->string())
                ->description('2-4 short semantic search queries.'),
        ];
    }

    /**
     * @return array<int, string>
     *
     * @codeCoverageIgnore
     */
    public function generateQueries(string $conversationContext): array
    {
        /** @var StructuredAgentResponse $response */
        $response = $this->prompt($conversationContext);

        /** @var array{queries?: array<int, string>} $data */
        $data = $response->toArray();

        return array_values(array_filter(
            $data['queries'] ?? [],
            static fn (mixed $q): bool => is_string($q) && mb_trim($q) !== '',
        ));
    }
}
