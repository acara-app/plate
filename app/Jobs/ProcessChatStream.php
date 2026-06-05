<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CompletePendingChatStreamTurn;
use App\Ai\AgentRequest;
use App\Ai\Agents\AgentRunner;
use App\Connectors\Broadcast\BroadcastConnector;
use App\Data\ChatStreamResult;
use App\Enums\ModelName;
use App\Events\ChatProcessing;
use App\Events\ChatRetrying;
use App\Events\ChatStreamFailed;
use App\Models\History;
use App\Models\User;
use App\Services\StreamAggregator;
use App\Services\StreamEventStore;
use Illuminate\Broadcasting\PrivateChannel;
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
#[Tries(3)]
final class ProcessChatStream implements ShouldQueue
{
    use Queueable;

    public $tries;

    /**
     * @param  list<array{type: string, name: ?string, base64: string, mime: ?string}>  $images
     */
    public function __construct(
        public int $userId,
        public string $conversationId,
        public string $content,
        public array $images,
        public string $modelName,
        public string $channel,
        public string $streamId,
        public string $userMessageId,
        public string $assistantMessageId,
    ) {
        $this->onQueue('chat');
    }

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

        broadcast(new ChatProcessing($this->userId, $this->conversationId));

        $request = new AgentRequest(
            message: $this->content,
            images: $this->base64Images(),
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
            $this->broadcastStreamEnd();
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

        broadcast(new ChatStreamFailed(
            userId: $this->userId,
            conversationId: $this->conversationId,
            message: 'Failed to process your message after multiple attempts.',
        ));
    }

    /**
     * @return list<Base64Image>
     */
    private function base64Images(): array
    {
        return array_values(array_map(
            fn (array $image): Base64Image => new Base64Image($image['base64'], $image['mime']),
            $this->images,
        ));
    }

    private function resetStateForRetry(StreamEventStore $events): bool
    {
        if ($this->attempts() <= 1) {
            return false;
        }

        if ($events->wasCancellationRequested($this->conversationId)) {
            $events->clear($this->conversationId);

            return true;
        }

        $events->clear($this->conversationId);

        broadcast(new ChatRetrying(
            userId: $this->userId,
            conversationId: $this->conversationId,
            attempt: $this->attempts(),
            maxAttempts: $this->tries,
        ));

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

    private function broadcastStreamEnd(): void
    {
        Broadcast::on(new PrivateChannel('chat.'.$this->userId))
            ->as('stream_end')
            ->with(['conversationId' => $this->conversationId])
            ->sendNow();
    }
}
