<?php

declare(strict_types=1);

namespace App\Ai\Agents\Memory;

use App\Ai\Agents\Memory\Concerns\UsesMemoryAgentConfig;
use App\Ai\SystemPrompt;
use App\Contracts\Ai\Memory\DecidesMemoryMerge;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;

#[MaxTokens(2048)]
#[Timeout(90)]
final class MemoryMergeDeciderAgent implements Agent, DecidesMemoryMerge, HasStructuredOutput
{
    use Promptable;
    use UsesMemoryAgentConfig;

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You decide whether a small cluster of semantically similar memories should be merged into one.',
                'Merging is good when the memories say the same thing or a refined version of it.',
                'Keep separate when they describe distinct events, contradictions, or independent preferences that happen to share vocabulary.',
            ],
            steps: [
                '1. Read each memory in the cluster (including its importance and categories).',
                '2. Ask: would a single combined statement lose meaningful information?',
                '3. If no, set should_merge=true and draft a single self-contained consolidated statement.',
                '4. If yes, set should_merge=false and explain briefly.',
                '5. If merging, assign an importance for the new memory (1-10). Default to the max of the sources when unsure.',
            ],
            output: [
                'should_merge and reasoning are always required.',
                'synthesized_content, importance, and categories should only be populated when should_merge is true.',
            ],
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'should_merge' => $schema->boolean()->required()
                ->description('True if the memories should be folded into one.'),
            'reasoning' => $schema->string()->required()
                ->description('Short explanation for the decision.'),
            'synthesized_content' => $schema->string()->nullable()
                ->description('If merging: the new self-contained combined memory.'),
            'importance' => $schema->integer()->nullable()
                ->description('If merging: importance 1-10.'),
            'categories' => $schema->array()->items($schema->string())->nullable()
                ->description('If merging: combined category tags.'),
        ];
    }

    /**
     * @return array{should_merge: bool, reasoning: string, synthesized_content: ?string, importance: ?int, categories: array<int, string>}
     *
     * @codeCoverageIgnore
     */
    public function decide(string $prompt): array
    {
        /** @var StructuredAgentResponse $response */
        $response = $this->prompt($prompt);

        /** @var array{should_merge?: bool, reasoning?: string, synthesized_content?: string|null, importance?: int|null, categories?: array<int, mixed>|null} $data */
        $data = $response->toArray();

        return [
            'should_merge' => (bool) ($data['should_merge'] ?? false),
            'reasoning' => isset($data['reasoning']) && is_string($data['reasoning']) ? $data['reasoning'] : '',
            'synthesized_content' => isset($data['synthesized_content']) && is_string($data['synthesized_content']) ? $data['synthesized_content'] : null,
            'importance' => isset($data['importance']) && is_numeric($data['importance']) ? $data['importance'] : null,
            'categories' => is_array($data['categories'] ?? null)
                ? array_values(array_filter($data['categories'], static fn (mixed $c): bool => is_string($c) && mb_trim($c) !== ''))
                : [],
        ];
    }
}
