<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Concerns\UsesConfiguredAgentModel;
use App\Contracts\Ai\UsesAgentModelProfiles;
use App\Contracts\SummarizesConversation;
use App\Models\ConversationSummary;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;

#[MaxTokens(4000)]
#[Timeout(90)]
final class ConversationSummarizerAgent implements Agent, HasProviderOptions, HasStructuredOutput, SummarizesConversation, UsesAgentModelProfiles
{
    use Promptable;
    use UsesConfiguredAgentModel;

    private ?ConversationSummary $previousSummary = null;

    public function instructions(): string
    {
        return view('ai.prompts.conversation-summarizer', [
            'previousSummary' => $this->previousSummary,
        ])->render();
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $stringList = (new ArrayType)->items($schema->string())->required();

        return [
            'summary' => $schema->string()->required(),
            'topics' => $stringList,
            'key_facts' => (new ArrayType)->items($schema->string())->required(),
            'unresolved_threads' => (new ArrayType)->items($schema->string())->required(),
            'resolved_threads' => (new ArrayType)->items($schema->string())->required(),
        ];
    }

    // @codeCoverageIgnoreStart
    public function summarize(string $conversationText, ?ConversationSummary $previousSummary): array
    {
        $this->previousSummary = $previousSummary;

        /** @var StructuredAgentResponse $response */
        $response = $this->prompt(
            prompt: $conversationText,
        );

        /** @var array{summary: string, topics: array<int, string>, key_facts: array<int, string>, unresolved_threads: array<int, string>, resolved_threads: array<int, string>} */
        return $response->toArray();
    }

    // @codeCoverageIgnoreEnd
}
