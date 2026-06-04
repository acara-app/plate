<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\Billing\EnforceAiUsageLimit;
use App\Ai\AgentBuilder;
use App\Ai\AgentRequest;
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

    private ?AgentRequest $currentRequest = null;

    public function __construct(
        private readonly AgentBuilder $agentBuilder,
        private readonly EnforceAiUsageLimit $enforceAiUsageLimit,
    ) {}

    public function run(AgentRequest $request, User $user): StreamableAgentResponse
    {
        $modelName = $this->prepare($request, $user);

        return $this
            ->continue($request->conversationId ?? '', as: $user)
            ->stream(
                prompt: $request->message,
                attachments: $request->images,
                provider: $modelName->labProvider(),
                model: $modelName->value,
            )
            ->usingVercelDataProtocol();
    }

    // @codeCoverageIgnoreStart
    public function runSync(AgentRequest $request, User $user): AgentResponse
    {
        $modelName = $this->prepare($request, $user);

        return $this
            ->continue($request->conversationId ?? '', as: $user)
            ->prompt(
                prompt: $request->message,
                attachments: $request->images,
                provider: $modelName->labProvider(),
                model: $modelName->value,
            );
    }

    // @codeCoverageIgnoreEnd

    public function instructions(): string
    {
        // @codeCoverageIgnoreStart
        if (! $this->currentRequest instanceof AgentRequest) {
            return '';
        }

        // @codeCoverageIgnoreEnd

        return $this->agentBuilder->buildInstructions($this->currentRequest, $this->user);
    }

    /**
     * @return array<int, Tool|ProviderTool|Agent>
     */
    public function tools(): array
    {
        // @codeCoverageIgnoreStart
        if (! $this->currentRequest instanceof AgentRequest) {
            return [];
        }

        // @codeCoverageIgnoreEnd

        return $this->agentBuilder->buildTools($this->currentRequest);
    }

    // @codeCoverageIgnoreStart
    protected function maxConversationMessages(): int
    {
        return ConfigHelper::int('altani.context.history_limit', 50);
    }

    // @codeCoverageIgnoreEnd

    private function prepare(AgentRequest $request, User $user): ModelName
    {
        $modelName = $request->modelName ?? ModelName::default();
        $this->enforceAiUsageLimit->handle($user, $modelName);

        $this->currentRequest = $request;
        $this->user = $user;

        return $modelName;
    }
}
