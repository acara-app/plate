<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Actions\Billing\EnforceAiUsageLimit;
use App\Actions\CompactStaleToolResults;
use App\Ai\AgentBuilder;
use App\Ai\AgentRequest;
use App\Ai\Concerns\UsesConfiguredAgentModel;
use App\Data\Ai\AgentModelData;
use App\Enums\ModelName;
use App\Models\History;
use App\Models\User;
use App\Services\Ai\AgentModelContext;
use App\Services\Ai\ConfiguredAgentModels;
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
use Laravel\Ai\Enums\MessageStatus;
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
    use UsesConfiguredAgentModel;

    private ?User $user = null;

    private ?AgentRequest $currentRequest = null;

    public function __construct(
        private readonly AgentBuilder $agentBuilder,
        private readonly EnforceAiUsageLimit $enforceAiUsageLimit,
        private readonly PlateConversationStore $conversationStore,
        private readonly CompactStaleToolResults $compactStaleToolResults,
    ) {}

    public function run(AgentRequest $request, User $user): StreamableAgentResponse
    {
        $modelName = $this->prepare($request, $user, appManagedPersistence: true);

        return $this
            ->continue($request->conversationId ?? '', as: $user)
            ->stream(
                prompt: $request->message,
                attachments: $request->images,
                provider: $modelName->provider,
                model: $modelName->model,
            );
    }

    public function resume(AgentRequest $request, User $user, Decisions $decisions): StreamableAgentResponse
    {
        $modelName = $this->prepare($request, $user, appManagedPersistence: true);

        return $this
            ->continue($request->conversationId ?? '', as: $user)
            ->stream(
                prompt: $decisions,
                provider: $modelName->provider,
                model: $modelName->model,
            );
    }

    // @codeCoverageIgnoreStart
    public function runSync(AgentRequest $request, User $user): AgentResponse
    {
        $modelName = $this->prepare($request, $user, appManagedPersistence: false);

        return resolve(AgentModelContext::class)->run(
            $user, $this->modelsForRequest($request, $user, $modelName),
            fn (): AgentResponse => $this
                ->continue($request->conversationId ?? '', as: $user)
                ->prompt(
                    prompt: $request->message,
                    attachments: $request->images,
                    provider: $modelName->provider,
                    model: $modelName->model,
                ),
        );
    }

    public function resumeSync(AgentRequest $request, User $user, Decisions $decisions): AgentResponse
    {
        $modelName = $this->prepare($request, $user, appManagedPersistence: false);

        return resolve(AgentModelContext::class)->run(
            $user, $this->modelsForRequest($request, $user, $modelName),
            fn (): AgentResponse => $this
                ->continue($request->conversationId ?? '', as: $user)
                ->prompt(
                    prompt: $decisions,
                    provider: $modelName->provider,
                    model: $modelName->model,
                ),
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

        $turn = array_values($this->conversationStore->assistantTurn($message));

        if ($message->status === MessageStatus::Paused) {
            return $turn;
        }

        return $this->compactStaleToolResults->handle($turn);
    }

    /** @return array<string, array<string, mixed>> */
    private function modelsForRequest(AgentRequest $request, User $user, AgentModelData $model): array
    {
        $models = $request->agentModels ?: resolve(ConfiguredAgentModels::class)->snapshot($user);
        $models[self::class] = $model->toSnapshot();

        return $models;
    }

    private function prepare(AgentRequest $request, User $user, bool $appManagedPersistence): AgentModelData
    {
        $this->setModelUser($user);
        $modelName = $request->model
            ?? ($request->modelName instanceof ModelName ? resolve(ConfiguredAgentModels::class)->forModel($request->modelName->value) : resolve(AgentModelContext::class)->resolve(self::class, $user));
        $this->selectedAgentModel = $modelName;
        $this->enforceAiUsageLimit->handle($user, $modelName);

        $this->currentRequest = new AgentRequest(
            message: $request->message, images: $request->images, conversationId: $request->conversationId,
            streamId: $request->streamId, model: $modelName, agentModels: $request->agentModels,
        );
        $this->user = $user;

        if ($appManagedPersistence) {
            Context::add(PlateConversationStore::APP_MANAGED, true);
        } else {
            Context::forget(PlateConversationStore::APP_MANAGED);
        }

        return $modelName;
    }
}
