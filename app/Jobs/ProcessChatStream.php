<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\PersistPartialChatStream;
use App\Ai\AgentPayload;
use App\Ai\Agents\AgentRunner;
use App\Enums\ModelName;
use App\Events\ChatProcessing;
use App\Events\ChatRetrying;
use App\Events\ChatStreamFailed;
use App\Models\User;
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
use Illuminate\Support\Str;
use Laravel\Ai\Files\Base64Image;
use Throwable;

#[MaxExceptions(3)]
#[Timeout(300)]
#[Tries(3)]
final class ProcessChatStream implements ShouldQueue
{
    public $tries;
    use Queueable;

    /**
     * @param  list<array{type: string, name: ?string, base64: string, mime: ?string}>  $images
     */
    public function __construct(
        public int $userId,
        public string $conversationId,
        public string $content,
        public array $images,
        public string $modelName,
        public string $channel = 'web',
        public string $streamId = '',
    ) {
        $this->onQueue('chat');

        if ($this->streamId === '') {
            $this->streamId = (string) Str::uuid7();
        }
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function handle(AgentRunner $agentRunner, StreamEventStore $events, PersistPartialChatStream $partial): void
    {
        if ($this->resetStateForRetry($events)) {
            return;
        }

        $user = User::query()->findOrFail($this->userId);
        Auth::login($user);

        Context::add('chat.channel', $this->channel);
        Context::add('chat.conversation_id', $this->conversationId);

        broadcast(new ChatProcessing($this->userId, $this->conversationId));

        $payload = new AgentPayload(
            userId: $this->userId,
            message: $this->content,
            images: $this->base64Images(),
            modelName: ModelName::tryFrom($this->modelName) ?? ModelName::default(),
            conversationId: $this->conversationId,
        );

        $stream = $agentRunner->runWithConversation($payload, $user, $this->conversationId);
        $channel = new PrivateChannel('chat.'.$this->userId);
        $sequence = 0;
        $cancelled = false;

        foreach ($stream as $event) {
            if ($events->wasCancellationRequested($this->conversationId)) {
                $cancelled = true;

                break;
            }

            $events->append($this->conversationId, $event, $sequence++);
            $event->broadcastNow($channel);
        }

        if ($cancelled) {
            $this->persistPartial($events, $partial);
            $this->broadcastStreamEnd();
        }

        $events->markComplete($this->conversationId);
    }

    public function failed(Throwable $e): void
    {
        report($e);

        $events = resolve(StreamEventStore::class);

        resolve(PersistPartialChatStream::class)->handle(
            conversationId: $this->conversationId,
            userId: $this->userId,
            prompt: $this->content,
            attachments: $this->images,
            assistantText: $events->aggregateText($this->conversationId),
            toolCalls: $events->aggregateToolCalls($this->conversationId),
            toolResults: $events->aggregateToolResults($this->conversationId),
            streamId: $this->streamId(),
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

    private function persistPartial(StreamEventStore $events, PersistPartialChatStream $partial): void
    {
        $partial->handle(
            conversationId: $this->conversationId,
            userId: $this->userId,
            prompt: $this->content,
            attachments: $this->images,
            assistantText: $events->aggregateText($this->conversationId),
            toolCalls: $events->aggregateToolCalls($this->conversationId),
            toolResults: $events->aggregateToolResults($this->conversationId),
            streamId: $this->streamId(),
        );
    }

    private function broadcastStreamEnd(): void
    {
        Broadcast::on(new PrivateChannel('chat.'.$this->userId))
            ->as('stream_end')
            ->with(['conversationId' => $this->conversationId])
            ->sendNow();
    }

    private function streamId(): string
    {
        if ($this->streamId === '') {
            $this->streamId = (string) Str::uuid7();
        }

        return $this->streamId;
    }
}
