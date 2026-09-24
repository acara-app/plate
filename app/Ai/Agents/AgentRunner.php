<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\Billing\EnforceAiUsageLimit;
use App\Ai\AgentBuilder;
use App\Ai\AgentRequest;
use App\Ai\ThinkingOptions;
use App\Enums\ModelName;
use App\Models\History;
use App\Models\User;
use App\Services\Ai\PlateConversationStore;
use App\Utilities\ConfigHelper;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;

#[Timeout(120)]
final class AgentRunner implements Agent, Conversational, HasProviderOptions, HasTools
{
    use Promptable, RemembersConversations;

    private ?User $user = null;

    private ?AgentRequest $currentRequest = null;

    public function __construct(
        private readonly AgentBuilder $agentBuilder,
        private readonly EnforceAiUsageLimit $enforceAiUsageLimit,
        private readonly PlateConversationStore $conversationStore,
    ) {}

    public function run(AgentRequest $request, User $user): StreamableAgentResponse
    {
        $modelName = $this->prepare($request, $user, appManagedPersistence: true);

        return $this
            ->continue($request->conversationId ?? '', as: $user)
            ->stream(
                prompt: $request->message,
                attachments: $request->images,
                provider: $modelName->labProvider(),
                model: $modelName->value,
            );
    }

    public function resume(AgentRequest $request, User $user, Decisions $decisions): StreamableAgentResponse
    {
        $modelName = $this->prepare($request, $user, appManagedPersistence: true);

        return $this
            ->continue($request->conversationId ?? '', as: $user)
            ->stream(
                prompt: $decisions,
                provider: $modelName->labProvider(),
                model: $modelName->value,
            );
    }

    // @codeCoverageIgnoreStart
    public function runSync(AgentRequest $request, User $user): AgentResponse
    {
        $modelName = $this->prepare($request, $user, appManagedPersistence: false);

        return $this
            ->continue($request->conversationId ?? '', as: $user)
            ->prompt(
                prompt: $request->message,
                attachments: $request->images,
                provider: $modelName->labProvider(),
                model: $modelName->value,
            );
    }

    public function resumeSync(AgentRequest $request, User $user, Decisions $decisions): AgentResponse
    {
        $modelName = $this->prepare($request, $user, appManagedPersistence: false);

        return $this
            ->continue($request->conversationId ?? '', as: $user)
            ->prompt(
                prompt: $decisions,
                provider: $modelName->labProvider(),
                model: $modelName->value,
            );
    }

    // @codeCoverageIgnoreEnd

    /**
     * @return list<Message>
     */
    public function messages(): iterable
    {
        if (! $this->currentRequest instanceof AgentRequest || ! $this->currentRequest->hasExistingConversation()) {
            return [];
        }

        $streamId = $this->currentRequest->streamId;

        $messages = History::query()
            ->select(['id', 'conversation_id', 'agent', 'role', 'content', 'steps', 'status', 'meta'])
            ->where('conversation_id', $this->currentRequest->conversationId)
            ->where('agent', self::class)
            ->orderByDesc('id')
            ->limit($this->maxConversationMessages() + 2)
            ->get()
            ->reverse()
            ->reject(fn (History $message): bool => $message->isPendingStreamAssistant()
                || ($streamId !== null && $message->belongsToChatStream($streamId)))
            ->flatMap(fn (History $message): array => $this->toAiMessages($message))
            ->all();

        return array_values($messages);
    }

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

        return $this->agentBuilder->buildTools($this->currentRequest, $this->user);
    }

    /**
     * @return array<string, mixed>
     */
    public function providerOptions(Lab|string $provider): array
    {
        // @codeCoverageIgnoreStart
        if (! $this->currentRequest instanceof AgentRequest) {
            return [];
        }

        // @codeCoverageIgnoreEnd

        $modelName = $this->currentRequest->modelName ?? ModelName::default();

        return ThinkingOptions::forModel($modelName, $provider);
    }

    // @codeCoverageIgnoreStart
    protected function maxConversationMessages(): int
    {
        return ConfigHelper::int('altani.context.history_limit', 50);
    }

    // @codeCoverageIgnoreEnd

    /**
     * @return list<Message>
     */
    private function toAiMessages(History $message): array
    {
        if ($message->role === MessageRole::User) {
            return [new Message(MessageRole::User, $message->content)];
        }

        return array_values($this->conversationStore->assistantTurn($message));
    }

    private function prepare(AgentRequest $request, User $user, bool $appManagedPersistence): ModelName
    {
        $modelName = $request->modelName ?? ModelName::default();
        $this->enforceAiUsageLimit->handle($user, $modelName);

        $this->currentRequest = $request;
        $this->user = $user;

        if ($appManagedPersistence) {
            Context::add(PlateConversationStore::APP_MANAGED, true);
        } else {
            Context::forget(PlateConversationStore::APP_MANAGED);
        }

        return $modelName;
    }
}
