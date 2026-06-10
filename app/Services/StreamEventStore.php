<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;

class StreamEventStore
{
    private const string KEY_PREFIX = 'plate:chat:stream:';

    private const string CANCEL_PREFIX = 'plate:chat:stream:cancel:';

    private const string COMPLETED_PREFIX = 'plate:chat:stream:completed:';

    private const int TTL_SECONDS = 600;

    /**
     * @param  array<string, mixed>  $event
     */
    public function append(string $conversationId, array $event, int $sequence): void
    {
        $payload = json_encode([
            'sequence' => $sequence,
            'type' => $event['type'] ?? null,
            'data' => $event,
        ], JSON_THROW_ON_ERROR);

        $this->redis()->zadd($this->streamKey($conversationId), [$payload => $sequence]);

        if ($sequence === 0) {
            $this->redis()->expire($this->streamKey($conversationId), self::TTL_SECONDS);
        }
    }

    /**
     * @return list<array{sequence: int, type: string, data: array<string, mixed>}>
     */
    public function eventsAfter(string $conversationId, int $afterSequence): array
    {
        /** @var array<int, string>|false $events */
        $events = $this->redis()->zrangebyscore(
            $this->streamKey($conversationId),
            (string) ($afterSequence + 1),
            '+inf',
        );

        if ($events === false || $events === []) {
            return [];
        }

        return array_values(array_map(
            fn (string $event): array => json_decode($event, true, flags: JSON_THROW_ON_ERROR),
            $events,
        ));
    }

    public function hasEvents(string $conversationId): bool
    {
        return (bool) $this->redis()->exists($this->streamKey($conversationId));
    }

    public function clear(string $conversationId): void
    {
        $this->redis()->del(
            $this->streamKey($conversationId),
            $this->cancelKey($conversationId),
            $this->completedKey($conversationId),
        );
    }

    public function markComplete(string $conversationId): void
    {
        $this->redis()->setex($this->completedKey($conversationId), self::TTL_SECONDS, '1');
        $this->redis()->expire($this->streamKey($conversationId), self::TTL_SECONDS);
        $this->clearCancellation($conversationId);
    }

    public function isStreaming(string $conversationId): bool
    {
        return $this->hasEvents($conversationId)
            && ! $this->redis()->exists($this->completedKey($conversationId));
    }

    public function requestCancellation(string $conversationId): void
    {
        $this->redis()->setex($this->cancelKey($conversationId), self::TTL_SECONDS, '1');
    }

    public function wasCancellationRequested(string $conversationId): bool
    {
        return (bool) $this->redis()->exists($this->cancelKey($conversationId));
    }

    public function clearCancellation(string $conversationId): void
    {
        $this->redis()->del($this->cancelKey($conversationId));
    }

    private function streamKey(string $conversationId): string
    {
        return self::KEY_PREFIX.$conversationId;
    }

    private function cancelKey(string $conversationId): string
    {
        return self::CANCEL_PREFIX.$conversationId;
    }

    private function completedKey(string $conversationId): string
    {
        return self::COMPLETED_PREFIX.$conversationId;
    }

    private function redis(): Connection
    {
        /** @var Connection $connection */
        $connection = Redis::connection();

        return $connection;
    }
}
