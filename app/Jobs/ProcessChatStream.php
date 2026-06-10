<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CompletePendingChatStreamTurn;
use App\Ai\AgentRequest;
use App\Ai\Agents\AgentRunner;
use App\Data\ChatStreamResult;
use App\Enums\ModelName;
use App\Models\History;
use App\Models\User;
use App\Services\BroadcastConnector;
use App\Services\ChatChannel;
use App\Services\StreamAggregator;
use App\Services\StreamEventStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Files\Base64Image;
use Throwable;

#[MaxExceptions(3)]
#[Timeout(300)]
#[Tries(ProcessChatStream::TRIES)]
final class ProcessChatStream implements ShouldQueue
{
    use Queueable;

    public const int TRIES = 3;

    public function __construct(
        public int $userId,
        public string $conversationId,
        public string $modelName,
        public string $channel,
        public string $streamId,
        public string $userMessageId,
        public string $assistantMessageId,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function handle(
        AgentRunner $agentRunner,
        StreamEventStore $events,
        BroadcastConnector $connector,
        CompletePendingChatStreamTurn $complete,
    ): void {
        if ($this->resetStateForRetry($events)) {
            $this->completePendingTurn($complete, new ChatStreamResult, History::STREAM_STATUS_CANCELLED);

            return;
        }

        $user = User::query()->findOrFail($this->userId);
        Auth::login($user);

        Context::add('chat.channel', $this->channel);
        Context::add('chat.conversation_id', $this->conversationId);

        $this->broadcastLifecycle('processing');

        $userMessage = History::query()->findOrFail($this->userMessageId);

        $request = new AgentRequest(
            message: $userMessage->content,
            images: $this->base64Images($userMessage->attachments ?? []),
            modelName: ModelName::tryFrom($this->modelName) ?? ModelName::default(),
            conversationId: $this->conversationId,
            streamId: $this->streamId,
        );

        $stream = $agentRunner->run($request, $user);
        $delivery = $connector->deliver($stream, $this->userId, $this->conversationId);

        $this->completePendingTurn(
            complete: $complete,
            result: $delivery->result,
            status: $delivery->cancelled ? History::STREAM_STATUS_CANCELLED : History::STREAM_STATUS_COMPLETED,
        );

        if ($delivery->cancelled) {
            $this->broadcastLifecycle('stream_end');
        }

        $events->markComplete($this->conversationId);
    }

    public function failed(Throwable $e): void
    {
        report($e);

        $events = resolve(StreamEventStore::class);
        $aggregator = resolve(StreamAggregator::class);

        resolve(CompletePendingChatStreamTurn::class)->handle(
            conversationId: $this->conversationId,
            userId: $this->userId,
            userMessageId: $this->userMessageId,
            assistantMessageId: $this->assistantMessageId,
            result: $aggregator->aggregateStoredEvents($events->eventsAfter($this->conversationId, -1)),
            status: History::STREAM_STATUS_FAILED,
        );

        $events->markComplete($this->conversationId);

        $this->broadcastLifecycle('error', [
            'message' => 'Failed to process your message after multiple attempts.',
        ]);
    }

    /**
     * @param  list<array{type?: string, name?: ?string, base64?: string, mime?: ?string}>  $attachments
     * @return list<Base64Image>
     */
    private function base64Images(array $attachments): array
    {
        return array_map(
            fn (array $image): Base64Image => new Base64Image($image['base64'] ?? '', $image['mime'] ?? null),
            $attachments,
        );
    }

    private function resetStateForRetry(StreamEventStore $events): bool
    {
        if ($this->attempts() <= 1) {
            return false;
        }

        $cancelled = $events->wasCancellationRequested($this->conversationId);
        $events->clear($this->conversationId);

        if ($cancelled) {
            return true;
        }

        $this->broadcastLifecycle('retrying', [
            'attempt' => $this->attempts(),
            'maxAttempts' => self::TRIES,
        ]);

        return false;
    }

    private function completePendingTurn(CompletePendingChatStreamTurn $complete, ChatStreamResult $result, string $status): void
    {
        $complete->handle(
            conversationId: $this->conversationId,
            userId: $this->userId,
            userMessageId: $this->userMessageId,
            assistantMessageId: $this->assistantMessageId,
            result: $result,
            status: $status,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function broadcastLifecycle(string $event, array $payload = []): void
    {
        Broadcast::on(ChatChannel::private($this->userId))
            ->as($event)
            ->with(['conversationId' => $this->conversationId, ...$payload])
            ->sendNow();
    }
}
