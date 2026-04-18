<?php

declare(strict_types=1);

namespace App\Contracts\Memory;

use App\Data\Memory\ConversationMessageData;
use DateTimeInterface;
use Illuminate\Support\Collection;

interface PullsConversationHistory
{
    /**
     * @return Collection<int, ConversationMessageData>
     */
    public function pendingMessagesFor(
        int $userId,
        ?DateTimeInterface $afterCreatedAt,
        ?string $afterId,
        int $limit,
    ): Collection;

    public function countPendingFor(
        int $userId,
        ?DateTimeInterface $afterCreatedAt,
        ?string $afterId,
    ): int;
}
