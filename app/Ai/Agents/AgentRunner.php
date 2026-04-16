<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\AgentBuilder;
use App\Ai\AgentPayload;
use App\Enums\ModelName;
use App\Models\User;
use App\Utilities\ConfigHelper;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;

#[Timeout(120)]
final class AgentRunner implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    private ?User $user = null;

    private ?AgentPayload $currentPayload = null;

    public function __construct(
        private readonly AgentBuilder $agentBuilder,
    ) {}

    public function run(AgentPayload $payload, User $user): StreamableAgentResponse
    {
        return $this->execute($payload, $user, '');
    }

    // @codeCoverageIgnoreStart
    public function runWithConversation(AgentPayload $payload, User $user, string $conversationId): StreamableAgentResponse
    {
        return $this->execute($payload, $user, $conversationId);
    }

    public function runSync(AgentPayload $payload, User $user, ?string $conversationId = null): AgentResponse
    {
        $this->currentPayload = $payload;
        $this->user = $user;
        $modelName = $payload->modelName ?? ModelName::GPT_5_4_MINI;

        return $this
            ->continue($conversationId ?? '', as: $user)
            ->prompt(
                prompt: $payload->message,
                attachments: $payload->images,
                provider: $modelName->labProvider(),
                model: $modelName->value,
            );
    }

    // @codeCoverageIgnoreEnd

    public function instructions(): string
    {
        // @codeCoverageIgnoreStart
        if (! $this->currentPayload instanceof AgentPayload) {
            return '';
        }

        // @codeCoverageIgnoreEnd

        return $this->agentBuilder->buildInstructions($this->currentPayload, $this->user);
    }

    /**
     * @return array<int, Tool|ProviderTool>
     */
    public function tools(): array
    {
        // @codeCoverageIgnoreStart
        if (! $this->currentPayload instanceof AgentPayload) {
            return [];
        }

        // @codeCoverageIgnoreEnd

        return $this->agentBuilder->buildTools($this->currentPayload);
    }

    // @codeCoverageIgnoreStart
    protected function maxConversationMessages(): int
    {
        return ConfigHelper::int('altani.context.history_limit', 50);
    }

    // @codeCoverageIgnoreEnd

    private function execute(AgentPayload $payload, User $user, string $conversationId): StreamableAgentResponse
    {
        $this->currentPayload = $payload;
        $this->user = $user;
        $modelName = $payload->modelName ?? ModelName::GPT_5_4_MINI;

        return $this
            ->continue($conversationId, as: $user)
            ->stream(
                prompt: $payload->message,
                attachments: $payload->images,
                provider: $modelName->labProvider(),
                model: $modelName->value,
            )
            ->usingVercelDataProtocol();
    }
}
