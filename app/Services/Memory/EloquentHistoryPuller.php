<?php

declare(strict_types=1);

namespace App\Services\Memory;

use App\Contracts\Memory\PullsConversationHistory;
use App\Data\Memory\ConversationMessageData;
use App\Models\History;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Laravel\Ai\Messages\MessageRole;

final readonly class EloquentHistoryPuller implements PullsConversationHistory
{
    /**
     * @return Collection<int, ConversationMessageData>
     */
    public function pendingMessagesFor(
        int $userId,
        ?DateTimeInterface $afterCreatedAt,
        ?string $afterId,
        int $limit,
    ): Collection {
        return $this->baseQuery($userId, $afterCreatedAt, $afterId)
            ->oldest()
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(static fn (History $message): ConversationMessageData => new ConversationMessageData(
                id: $message->id,
                createdAt: $message->created_at,
                role: $message->role->value,
                content: $message->content,
            ));
    }

    public function countPendingFor(
        int $userId,
        ?DateTimeInterface $afterCreatedAt,
        ?string $afterId,
    ): int {
        return $this->baseQuery($userId, $afterCreatedAt, $afterId)->count();
    }

    /**
     * @return Builder<History>
     */
    private function baseQuery(int $userId, ?DateTimeInterface $cursorAt, ?string $cursorId): Builder
    {
        $query = History::query()
            ->where('user_id', $userId)
            ->whereIn('role', [MessageRole::User->value, MessageRole::Assistant->value]);

        if ($cursorAt !== null) {
            $query->where(function (Builder $q) use ($cursorAt, $cursorId): void {
                $q->where('created_at', '>', $cursorAt)
                    ->orWhere(function (Builder $q2) use ($cursorAt, $cursorId): void {
                        $q2->where('created_at', '=', $cursorAt)
                            ->when($cursorId !== null, fn (Builder $q3) => $q3->where('id', '>', $cursorId));
                    });
            });
        }

        return $query;
    }
}
