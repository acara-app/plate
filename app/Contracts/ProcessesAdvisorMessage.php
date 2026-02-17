<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Actions\ProcessAdvisorMessageAction;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;

#[Bind(ProcessAdvisorMessageAction::class)]
interface ProcessesAdvisorMessage
{
    /**
     * @return array{response: string, conversation_id: string}
     */
    public function handle(User $user, string $message, ?string $conversationId = null): array;

    public function resetConversation(User $user): string;
}
