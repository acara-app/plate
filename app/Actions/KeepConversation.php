<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Conversation;

/** @codeCoverageIgnore */
final readonly class KeepConversation
{
    public function handle(Conversation $conversation): void
    {
        $conversation->forceFill(['kept_at' => now()])->save();
    }
}
