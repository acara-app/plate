<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Approvals\ApproveAgentApproval;
use App\Actions\Approvals\RejectAgentApproval;
use App\Models\AgentApproval;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final readonly class ApprovalController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private ApproveAgentApproval $approveAgentApproval,
        private RejectAgentApproval $rejectAgentApproval,
    ) {}

    public function show(Conversation $conversation, AgentApproval $approval): JsonResponse
    {
        $this->ensureAccess($conversation, $approval);

        return response()->json($this->present($approval));
    }

    public function approve(Conversation $conversation, AgentApproval $approval): JsonResponse
    {
        $this->ensureAccess($conversation, $approval);

        return response()->json($this->present(
            $this->approveAgentApproval->handle($approval, $this->user)
        ));
    }

    public function reject(Conversation $conversation, AgentApproval $approval): JsonResponse
    {
        $this->ensureAccess($conversation, $approval);

        return response()->json($this->present(
            $this->rejectAgentApproval->handle($approval, $this->user)
        ));
    }

    private function ensureAccess(Conversation $conversation, AgentApproval $approval): void
    {
        Gate::authorize('view', $conversation);

        abort_unless(
            $approval->conversation_id === $conversation->id && $approval->user_id === $this->user->id,
            404,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function present(AgentApproval $approval): array
    {
        /** @var array<string, mixed> $card */
        $card = $approval->toCardData()->toArray();

        return $card;
    }
}
