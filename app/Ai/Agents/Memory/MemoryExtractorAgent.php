<?php

declare(strict_types=1);

namespace App\Ai\Agents\Memory;

use App\Ai\Agents\Memory\Concerns\UsesMemoryAgentConfig;
use App\Ai\SystemPrompt;
use App\Contracts\Ai\Memory\ExtractsMemoriesFromConversation;
use App\Enums\MemoryCategory;
use App\Enums\MemoryType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;

#[MaxTokens(4096)]
#[Timeout(120)]
final class MemoryExtractorAgent implements Agent, ExtractsMemoriesFromConversation, HasStructuredOutput
{
    use Promptable;
    use UsesMemoryAgentConfig;

    public function instructions(): string
    {
        $types = implode(', ', MemoryType::values());
        $categories = implode(', ', MemoryCategory::values());

        return (string) new SystemPrompt(
            background: [
                'You read a slice of a user conversation and extract facts worth remembering long-term.',
                'Only extract things that will still matter weeks later (preferences, goals, relationships, stable facts).',
                'Ignore chit-chat, time-bounded task details, or anything the user only said in passing.',
            ],
            steps: [
                '1. Read the formatted conversation turns.',
                '2. Decide whether any memory is worth extracting. If nothing qualifies, set should_extract to false and return an empty memories array.',
                '3. For each extraction: write the memory as a complete, self-contained statement ("User prefers oat milk" not "they like it").',
                '4. Pick memory_type from: '.$types,
                '5. Pick 1-3 categories from: '.$categories.' (or freeform snake_case if none fit).',
                '6. Rate importance 1-10: 1-3 = trivial, 4-6 = useful, 7-8 = important, 9-10 = critical/rare.',
            ],
            output: [
                'should_extract is always required.',
                'memories must be an array — empty if nothing to extract.',
                'Never invent facts. Only record what is explicitly stated in the conversation.',
            ],
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'should_extract' => $schema->boolean()->required()
                ->description('True if anything is worth extracting.'),
            'memories' => (new ArrayType)
                ->items($schema->object(fn ($s): array => [
                    'content' => $s->string()->required()->description('Complete self-contained memory statement.'),
                    'memory_type' => $s->string()->required()->description('One of: '.implode(', ', MemoryType::values())),
                    'categories' => (new ArrayType)->items($s->string())->description('Category labels.'),
                    'importance' => $s->integer()->description('Priority 1-10.'),
                    'context' => $s->string()->nullable()->description('When/where/why learned.'),
                ]))
                ->description('List of memories to extract. Empty if should_extract is false.'),
        ];
    }

    /**
     * @return array{should_extract: bool, memories: array<int, array<string, mixed>>}
     *
     * @codeCoverageIgnore
     */
    public function extractFromConversation(string $formattedConversation): array
    {
        /** @var StructuredAgentResponse $response */
        $response = $this->prompt(
            "Analyze this conversation and extract memories worth remembering long-term:\n\n".$formattedConversation,
        );

        /** @var array{should_extract?: bool, memories?: array<int, array<string, mixed>>} $data */
        $data = $response->toArray();

        return [
            'should_extract' => (bool) ($data['should_extract'] ?? false),
            'memories' => is_array($data['memories'] ?? null) ? $data['memories'] : [],
        ];
    }
}
