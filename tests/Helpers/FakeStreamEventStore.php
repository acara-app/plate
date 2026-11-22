<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Services\StreamEventStore;
use stdClass;

final class FakeStreamEventStore extends StreamEventStore
{
    /**
     * @var list<stdClass>
     */
    public array $events = [];

    public bool $streaming = false;

    public bool $repeatsEvents = false;

    public function clear(string $conversationId): void {}

    public function eventsAfter(string $conversationId, int $afterSequence): array
    {
        return array_values(array_map(
            fn (stdClass $event): array => ['sequence' => $event->sequence, 'type' => $event->type, 'data' => $event],
            array_filter($this->events, fn (stdClass $event): bool => $this->repeatsEvents || $event->sequence > $afterSequence),
        ));
    }

    public function isStreaming(string $conversationId): bool
    {
        return $this->streaming;
    }
}
