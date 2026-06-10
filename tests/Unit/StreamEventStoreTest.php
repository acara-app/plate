<?php

declare(strict_types=1);

use App\Services\StreamEventStore;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;

it('stores stream events as ordered redis replay entries and sets the ttl once', function (): void {
    $connection = Mockery::mock(Connection::class);

    Redis::shouldReceive('connection')->times(3)->andReturn($connection);

    $connection->shouldReceive('zadd')
        ->once()
        ->withArgs(function (string $key, array $payload): bool {
            $encoded = array_key_first($payload);
            $decoded = json_decode((string) $encoded, true, flags: JSON_THROW_ON_ERROR);

            return $key === 'plate:chat:stream:conversation-1'
                && $payload[$encoded] === 0
                && $decoded['sequence'] === 0
                && $decoded['type'] === 'text_delta'
                && $decoded['data']['delta'] === 'Hello';
        });

    $connection->shouldReceive('expire')
        ->once()
        ->with('plate:chat:stream:conversation-1', 600);

    $connection->shouldReceive('zadd')
        ->once()
        ->withArgs(fn (string $key, array $payload): bool => $payload[array_key_first($payload)] === 7);

    $store = resolve(StreamEventStore::class);

    $store->append('conversation-1', ['type' => 'text_delta', 'delta' => 'Hello'], 0);
    $store->append('conversation-1', ['type' => 'text_delta', 'delta' => ' there'], 7);
});

it('returns replay events after a sequence number', function (): void {
    $connection = Mockery::mock(Connection::class);
    $payload = json_encode([
        'sequence' => 3,
        'type' => 'text_delta',
        'data' => ['delta' => 'there'],
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
            ],
        ]);
});

it('marks completed streams without deleting replay events', function (): void {
    $connection = Mockery::mock(Connection::class);

    Redis::shouldReceive('connection')->times(5)->andReturn($connection);

    $connection->shouldReceive('setex')
        ->once()
        ->with('plate:chat:stream:completed:conversation-1', 600, '1');

    $connection->shouldReceive('expire')
        ->once()
        ->with('plate:chat:stream:conversation-1', 600);

    $connection->shouldReceive('del')
        ->once()
        ->with('plate:chat:stream:cancel:conversation-1');

    resolve(StreamEventStore::class)->markComplete('conversation-1');

    $connection->shouldReceive('exists')
        ->once()
        ->with('plate:chat:stream:conversation-1')
        ->andReturn(1);

    $connection->shouldReceive('exists')
        ->once()
        ->with('plate:chat:stream:completed:conversation-1')
        ->andReturn(1);

    expect(resolve(StreamEventStore::class)->isStreaming('conversation-1'))->toBeFalse();
});
