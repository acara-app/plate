<?php

declare(strict_types=1);

namespace App\Data;

/** @codeCoverageIgnore */
final readonly class ChatStreamDelivery
{
    /**
     * @param  list<array<string, mixed>>  $providerContentBlocks  raw paused-turn replay state; persisted for a resume, never sent to clients
     */
    public function __construct(
        public ChatStreamResult $result,
        public bool $cancelled,
        public array $providerContentBlocks = [],
        public ?string $provider = null,
    ) {}
}
