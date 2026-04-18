<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\SummarizeConversationAction;
use App\Models\Conversation;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

#[MaxExceptions(3)]
#[Timeout(120)]
final class SummarizeConversationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Conversation $conversation,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->conversation->id),
        ];
    }

    public function uniqueId(): string
    {
        return $this->conversation->id;
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(SummarizeConversationAction $action): void
    {
        $action->handle($this->conversation);

        $this->conversation->update(['summarization_dispatched_at' => null]);
    }

    public function failed(Throwable $exception): void
    {
        $this->conversation->update(['summarization_dispatched_at' => null]);
    }
}
