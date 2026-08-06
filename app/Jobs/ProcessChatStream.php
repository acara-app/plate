<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CompletePendingChatStreamTurn;
use App\Ai\AgentRequest;
use App\Ai\Agents\AgentRunner;
use App\Data\ChatStreamDelivery;
use App\Data\ChatStreamResult;
use App\Enums\ModelName;
use App\Models\History;
use App\Models\User;
use App\Services\BroadcastConnector;
use App\Services\ChatChannel;
use App\Services\StreamAggregator;
use App\Services\StreamEventStore;
use App\Utilities\LanguageUtil;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Files\Base64Image;
use Throwable;

#[MaxExceptions(3)]
#[Timeout(300)]
#[Tries(ProcessChatStream::TRIES)]
final class ProcessChatStream implements ShouldQueue
{
    use Queueable;

    public const int TRIES = 3;

    /**
     * @param  list<array{id: string, action: string, result?: string|null}>|null  $decisions  tool approval decisions that resume a paused turn
     */
    public function __construct(
        public int $userId,
        public string $conversationId,
        public string $modelName,
        public string $channel,
        public string $streamId,
        public ?string $userMessageId,
        public string $assistantMessageId,
        public ?string $locale = null,
        public ?array $decisions = null,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        // @codeCoverageIgnoreStart
        return [5, 15, 30];
    }

    public function handle(
        AgentRunner $agentRunner,
        StreamEventStore $events,
        BroadcastConnector $connector,
        CompletePendingChatStreamTurn $complete,
    ): void {
        $previousLocale = App::getLocale();

        try {
            $user = User::query()->findOrFail($this->userId);

            if ($this->resetStateForRetry($events)) {
                $this->completePendingTurn(
                    complete: $complete,
                    user: $user,
                    delivery: new ChatStreamDelivery(new ChatStreamResult, cancelled: true),
                    status: History::STREAM_STATUS_CANCELLED,
                );

                return;
            }

            Auth::login($user);
            App::setLocale(LanguageUtil::resolve($this->locale ?? $user->locale)['code']);

            Context::add('chat.channel', $this->channel);
            Context::add('chat.conversation_id', $this->conversationId);

            $this->broadcastLifecycle('processing');

            $userMessage = $this->userMessageId === null
                ? null
                : History::query()->findOrFail($this->userMessageId);

            $request = new AgentRequest(
                message: $userMessage->content ?? '',
                images: $this->base64Images($userMessage->attachments ?? []),
                modelName: ModelName::tryFrom($this->modelName) ?? ModelName::default(),
                conversationId: $this->conversationId,
                streamId: $this->streamId,
            );

            $decisions = $this->approvalDecisions();

            $stream = $decisions instanceof Decisions
                ? $agentRunner->resume($request, $user, $decisions)
                : $agentRunner->run($request, $user);

            $delivery = $connector->deliver($stream, $this->userId, $this->conversationId);

            $this->completePendingTurn(
                complete: $complete,
                user: $user,
                delivery: $delivery,
                status: $delivery->cancelled ? History::STREAM_STATUS_CANCELLED : History::STREAM_STATUS_COMPLETED,
            );

            if ($delivery->cancelled) {
                $this->broadcastLifecycle('stream_end');
            }

            $events->markComplete($this->conversationId);
        } finally {
            App::setLocale($previousLocale);
        }
    }

    public function failed(Throwable $e): void
    {
        report($e);

        $events = resolve(StreamEventStore::class);
        $aggregator = resolve(StreamAggregator::class);
        $user = User::query()->find($this->userId);

        if ($user instanceof User) {
            resolve(CompletePendingChatStreamTurn::class)->handle(
                conversationId: $this->conversationId,
                user: $user,
                userMessageId: $this->userMessageId,
                assistantMessageId: $this->assistantMessageId,
                result: $aggregator->aggregateStoredEvents($events->eventsAfter($this->conversationId, -1)),
                status: History::STREAM_STATUS_FAILED,
            );
        }

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

    private function completePendingTurn(CompletePendingChatStreamTurn $complete, User $user, ChatStreamDelivery $delivery, string $status): void
    {
        $complete->handle(
            conversationId: $this->conversationId,
            user: $user,
            userMessageId: $this->userMessageId,
            assistantMessageId: $this->assistantMessageId,
            result: $delivery->result,
            status: $status,
            providerContentBlocks: $delivery->providerContentBlocks,
            provider: $delivery->provider,
        );
        // @codeCoverageIgnoreEnd
    }

    private function approvalDecisions(): ?Decisions
    {
        if ($this->decisions === null || $this->decisions === []) {
            return null;
        }

        return Decisions::from(collect($this->decisions)
            ->mapWithKeys(fn (array $decision): array => [
                $decision['id'] => $decision['action'] === 'approve'
                    ? Decision::approve()
                    : Decision::reject($decision['result'] ?? null),
            ])
            ->all());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    // @codeCoverageIgnoreStart
    private function broadcastLifecycle(string $event, array $payload = []): void
    {
        Broadcast::on(ChatChannel::private($this->userId))
            ->as($event)
            ->with(['conversationId' => $this->conversationId, ...$payload])
            ->sendNow();
    }

    // @codeCoverageIgnoreEnd
}
