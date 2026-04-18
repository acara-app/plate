<?php

declare(strict_types=1);

namespace App\Services\Memory;

use App\Contracts\Memory\PullsConversationHistory;
use App\Data\Memory\ConversationMessageData;
use DateTimeInterface;
use Illuminate\Support\Collection;

final readonly class NullConversationHistoryPuller implements PullsConversationHistory
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
        return new Collection;
    }

    public function countPendingFor(
        int $userId,
        ?DateTimeInterface $afterCreatedAt,
        ?string $afterId,
    ): int {
        return 0;
    }
}
