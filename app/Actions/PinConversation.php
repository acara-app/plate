<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Conversation;

/** @codeCoverageIgnore */
final readonly class PinConversation
{
    public function handle(Conversation $conversation): void
    {
        $conversation->forceFill(['pinned_at' => now()])->save();
    }
}
