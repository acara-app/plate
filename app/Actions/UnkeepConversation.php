<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Conversation;

/** @codeCoverageIgnore */
final readonly class UnkeepConversation
{
    public function handle(Conversation $conversation): void
    {
        $conversation->forceFill([
            'kept_at' => null,
            'updated_at' => now(),
        ])->save();
    }
}
