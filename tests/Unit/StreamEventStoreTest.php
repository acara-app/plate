<?php

declare(strict_types=1);

use App\Services\StreamEventStore;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Laravel\Ai\Streaming\Events\TextDelta;

it('stores stream events as ordered redis replay entries', function (): void {
    $connection = Mockery::mock(Connection::class);

    Redis::shouldReceive('connection')->twice()->andReturn($connection);

    $connection->shouldReceive('zadd')
        ->once()
        ->withArgs(function (string $key, array $payload): bool {
            $encoded = array_key_first($payload);
            $decoded = json_decode((string) $encoded, true, flags: JSON_THROW_ON_ERROR);

            return $key === 'plate:chat:stream:conversation-1'
                && $payload[$encoded] === 7
                && $decoded['sequence'] === 7
                && $decoded['type'] === 'text_delta'
                && $decoded['data']['delta'] === 'Hello';
        });

    $connection->shouldReceive('expire')
        ->once()
        ->with('plate:chat:stream:conversation-1', 600);

    resolve(StreamEventStore::class)->append(
        'conversation-1',
        new TextDelta('event-1', 'message-1', 'Hello', now()->timestamp),
        7,
    );
});

it('returns replay events after a sequence number', function (): void {
    $connection = Mockery::mock(Connection::class);
    $payload = json_encode([
        'sequence' => 3,
        'type' => 'text_delta',
        'data' => ['delta' => 'there'],
        'vercel' => ['type' => 'text-delta', 'delta' => 'there'],
    ], JSON_THROW_ON_ERROR);

    Redis::shouldReceive('connection')->once()->andReturn($connection);

    $connection->shouldReceive('zrangebyscore')
        ->once()
        ->with('plate:chat:stream:conversation-1', '3', '+inf')
        ->andReturn([$payload]);

    expect(resolve(StreamEventStore::class)->eventsAfter('conversation-1', 2))
        ->toBe([
            [
                'sequence' => 3,
                'type' => 'text_delta',
                'data' => ['delta' => 'there'],
                'vercel' => ['type' => 'text-delta', 'delta' => 'there'],
            ],
        ]);
});
