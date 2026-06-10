<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ChatStreamDelivery
{
    public function __construct(
        public ChatStreamResult $result,
        public bool $cancelled,
    ) {}
}
