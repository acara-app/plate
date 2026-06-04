<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Laravel\Ai\Streaming\Events\StreamEvent;

class StreamEventStore
{
    private const string KEY_PREFIX = 'plate:chat:stream:';

    private const string CANCEL_PREFIX = 'plate:chat:stream:cancel:';

    private const string COMPLETED_PREFIX = 'plate:chat:stream:completed:';

    private const int TTL_SECONDS = 600;

    public function append(string $conversationId, StreamEvent $event, int $sequence): void
    {
        $payload = json_encode([
            'sequence' => $sequence,
            'type' => $event->type(),
            'data' => $event->toArray(),
            'vercel' => $event->toVercelProtocolArray(),
        ], JSON_THROW_ON_ERROR);

        $this->redis()->zadd($this->streamKey($conversationId), [$payload => $sequence]);
        $this->redis()->expire($this->streamKey($conversationId), self::TTL_SECONDS);
    }

    /**
     * @return list<array{sequence: int, type: string, data: array<string, mixed>, vercel: array<string, mixed>|null}>
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

    public function lastSequence(string $conversationId): int
    {
        /** @var array<int, string>|false $events */
        $events = $this->redis()->zrevrange($this->streamKey($conversationId), 0, 0);

        if ($events === false || $events === []) {
            return 0;
        }

        /** @var array{sequence: int} $decoded */
        $decoded = json_decode($events[0], true, flags: JSON_THROW_ON_ERROR);

        return $decoded['sequence'];
    }

    public function hasEvents(string $conversationId): bool
    {
        return (bool) $this->redis()->exists($this->streamKey($conversationId));
    }

    public function clear(string $conversationId): void
    {
        $this->redis()->del($this->streamKey($conversationId));
        $this->clearCancellation($conversationId);
        $this->redis()->del($this->completedKey($conversationId));
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

    public function aggregateText(string $conversationId): string
    {
        return collect($this->eventsAfter($conversationId, -1))
            ->filter(fn (array $event): bool => $event['type'] === 'text_delta')
            ->map(fn (array $event): string => (string) ($event['data']['delta'] ?? ''))
            ->join('');
    }

    /**
     * @return list<array{id: string, name: string, arguments: array<string, mixed>, result_id: mixed, reasoning_id: mixed, reasoning_summary: mixed}>
     */
    public function aggregateToolCalls(string $conversationId): array
    {
        return collect($this->eventsAfter($conversationId, -1))
            ->filter(fn (array $event): bool => $event['type'] === 'tool_call')
            ->map(fn (array $event): array => [
                'id' => (string) $event['data']['tool_id'],
                'name' => (string) $event['data']['tool_name'],
                'arguments' => is_array($event['data']['arguments'] ?? null)
                    ? $event['data']['arguments']
                    : [],
                'result_id' => $event['data']['result_id'] ?? null,
                'reasoning_id' => $event['data']['reasoning_id'] ?? null,
                'reasoning_summary' => $event['data']['reasoning_summary'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string, arguments: array<string, mixed>, result: mixed, result_id: mixed}>
     */
    public function aggregateToolResults(string $conversationId): array
    {
        return collect($this->eventsAfter($conversationId, -1))
            ->filter(fn (array $event): bool => $event['type'] === 'tool_result')
            ->map(fn (array $event): array => [
                'id' => (string) $event['data']['tool_id'],
                'name' => (string) $event['data']['tool_name'],
                'arguments' => [],
                'result' => $event['data']['result'] ?? null,
                'result_id' => $event['data']['result_id'] ?? null,
            ])
            ->values()
            ->all();
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
