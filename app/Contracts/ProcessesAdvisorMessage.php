<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Actions\ProcessAdvisorMessageAction;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Files\Base64Image;

#[Bind(ProcessAdvisorMessageAction::class)]
interface ProcessesAdvisorMessage
{
    /**
     * @param  array<int, Base64Image>  $attachments
     * @return array{response: string, conversation_id: string, pending_approvals: list<PendingApproval>}
     */
    public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array;

    /**
     * @return array{response: string, conversation_id: string, pending_approvals: list<PendingApproval>}
     */
    public function resume(User $user, string $conversationId, Decisions $decisions): array;

    public function resetConversation(User $user): string;
}
