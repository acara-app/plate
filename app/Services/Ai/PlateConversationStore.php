<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Actions\AbandonPendingApprovals;
use App\Models\History;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\ProviderToolCall;
use Laravel\Ai\Responses\Data\Step;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Storage\DatabaseConversationStore;
use Throwable;

/**
 * @phpstan-type TStoredStep array{content: string, tool_calls: list<array<string, mixed>>, reasoning: string, replay_blocks: array<array-key, mixed>, provider_tool_calls: list<array<array-key, mixed>>}
 */
final class PlateConversationStore extends DatabaseConversationStore
{
    public const string APP_MANAGED = 'chat.app_managed_persistence';

    public static function appManaged(): bool
    {
        return Context::get(self::APP_MANAGED) === true;
    }

    public function storeUserMessage(string $conversationId, ?string $participantType, string|int|null $participantId, string $agent, UserMessage $message): string
    {
        if (self::appManaged()) {
            return '';
        }

        resolve(AbandonPendingApprovals::class)->handle($conversationId);

        return parent::storeUserMessage($conversationId, $participantType, $participantId, $agent, $message);
    }

    public function storeAssistantMessage(string $conversationId, ?string $participantType, string|int|null $participantId, AgentPrompt $prompt, AgentResponse $response, ?Throwable $exception = null): ?string
    {
        if (self::appManaged()) {
            return null;
        }

        return parent::storeAssistantMessage($conversationId, $participantType, $participantId, $prompt, $response, $exception);
    }

    /**
     * @return array<int, Message>
     */
    public function assistantTurn(History $message): array
    {
        return $this->assistantTurnFrom((object) [
            'steps' => json_encode($message->steps ?? []),
            'status' => ($message->status ?? MessageStatus::Completed)->value,
            'meta' => json_encode($message->meta ?? []),
        ]);
    }

    /**
     * @param  Collection<int, Step>  $steps
     * @param  array<string, string|null>  $pendingApprovals
     * @return list<TStoredStep>
     */
    public function storableSteps(Collection $steps, array $pendingApprovals): array
    {
        return array_values($steps->map(fn (Step $step): array => [
            'content' => $step->text,
            'tool_calls' => $this->toolCallsFor($step->toolCalls, $step->toolResults, collect($pendingApprovals)),
            'reasoning' => $step->reasoning,
            'replay_blocks' => $pendingApprovals === [] ? [] : $step->replayBlocks,
            'provider_tool_calls' => array_values(array_map(fn (ProviderToolCall $call): array => $call->toArray(), $step->providerToolCalls)),
        ])->all());
    }

    /**
     * @param  list<array<string, mixed>>  $toolCalls
     * @param  list<array<string, mixed>>  $toolResults
     * @param  array<string, string|null>  $pendingApprovals
     * @return TStoredStep
     */
    public function storableStep(string $text, array $toolCalls, array $toolResults, array $pendingApprovals): array
    {
        return [
            'content' => $text,
            'tool_calls' => $this->toolCallsFor(
                array_map(ToolCall::fromArray(...), $toolCalls),
                array_map(ToolResult::fromArray(...), $toolResults),
                collect($pendingApprovals),
            ),
            'reasoning' => '',
            'replay_blocks' => [],
            'provider_tool_calls' => [],
        ];
    }
}
