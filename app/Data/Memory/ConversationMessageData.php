<?php

declare(strict_types=1);

namespace App\Data\Memory;

use DateTimeInterface;
use Spatie\LaravelData\Data;

/**
 * @codeCoverageIgnore
 */
final class ConversationMessageData extends Data
{
    public function __construct(
        public string $id,
        public DateTimeInterface $createdAt,
        public string $role,
        public string $content,
    ) {}
}
