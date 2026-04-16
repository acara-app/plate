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

#[MaxTokens(2048)]
#[Timeout(90)]
final class MemoryReflectorAgent implements Agent, HasStructuredOutput
{
    use Promptable;
    use UsesMemoryAgentConfig;

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are a reflective-synthesis assistant.',
                'You review a batch of recent user memories and synthesize high-signal insights.',
            ],
            steps: [
                '1. Read the memory statements provided, including their importance and categories.',
                '2. Identify patterns, contradictions, habits, or emerging goals.',
                '3. Produce 1-5 concise insights (one sentence each).',
            ],
            output: [
                'Return only the insights array.',
                'Each insight should be a single declarative sentence.',
                'Do not restate individual memories verbatim.',
            ],
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'insights' => (new ArrayType)
                ->items($schema->string())
                ->description('1-5 one-sentence synthesized insights.'),
        ];
    }

    /**
     * @return array<int, string>
     *
     * @codeCoverageIgnore
     */
    public function reflect(string $prompt): array
    {
        /** @var StructuredAgentResponse $response */
        $response = $this->prompt($prompt);

        /** @var array{insights?: array<int, string>} $data */
        $data = $response->toArray();

        return array_values(array_filter($data['insights'] ?? [], is_string(...)));
    }
}
