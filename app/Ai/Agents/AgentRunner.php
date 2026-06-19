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
use App\Utilities\ConfigHelper;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Messages\ToolResultMessage;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;
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
    ) {}

    public function run(AgentRequest $request, User $user): StreamableAgentResponse
    {
        $modelName = $this->prepare($request, $user);
        $this->conversationId = $request->conversationId;
        $this->conversationUser = null;

        return $this
            ->stream(
                prompt: $request->message,
                attachments: $request->images,
                provider: $modelName->labProvider(),
                model: $modelName->value,
            );
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
            ->select(['id', 'conversation_id', 'agent', 'role', 'content', 'tool_calls', 'tool_results', 'meta'])
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

        return $this->agentBuilder->buildTools($this->currentRequest);
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
        $toolCalls = collect($message->tool_calls ?? [])->values();
        $toolResults = collect($message->tool_results ?? [])->values();

        if ($message->role === MessageRole::User) {
            return [new Message(MessageRole::User, $message->content)];
        }

        if ($toolCalls->isNotEmpty()) {
            // @codeCoverageIgnoreStart
            $messages = [
                new AssistantMessage(
                    $message->content ?: '',
                    $toolCalls->map(fn (array $toolCall): ToolCall => new ToolCall(
                        id: $toolCall['id'],
                        name: $toolCall['name'],
                        arguments: $toolCall['arguments'] ?? [],
                        resultId: $toolCall['result_id'] ?? null,
                        reasoningId: $toolCall['reasoning_id'] ?? null,
                        reasoningSummary: $toolCall['reasoning_summary'] ?? null,
                    ))
                ),
            ];

            if ($toolResults->isNotEmpty()) {
                $messages[] = new ToolResultMessage(
                    $toolResults->map(fn (array $toolResult): ToolResult => new ToolResult(
                        id: $toolResult['id'],
                        name: $toolResult['name'],
                        arguments: $toolResult['arguments'] ?? [],
                        result: $toolResult['result'] ?? null,
                        resultId: $toolResult['result_id'] ?? null,
                    ))
                );
            }

            return $messages;
            // @codeCoverageIgnoreEnd
        }

        return [new AssistantMessage($message->content)];
    }

    private function prepare(AgentRequest $request, User $user): ModelName
    {
        $modelName = $request->modelName ?? ModelName::default();
        $this->enforceAiUsageLimit->handle($user, $modelName);

        $this->currentRequest = $request;
        $this->user = $user;

        return $modelName;
    }
}
