<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateConversationTitleAction;
use App\Models\Conversation;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Middleware\WithoutOverlapping;

#[MaxExceptions(3)]
#[Timeout(60)]
final class GenerateConversationTitleJob implements ShouldBeUnique, ShouldQueue
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

    public function handle(GenerateConversationTitleAction $action): void
    {
        $action->handle($this->conversation);
    }
}
