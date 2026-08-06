<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Actions\ResumeChatStream;
use App\Http\Requests\SubmitApprovalDecisionsRequest;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final readonly class ApprovalDecisionController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private ResumeChatStream $resumeChatStream,
    ) {}

    public function __invoke(
        SubmitApprovalDecisionsRequest $request,
        Conversation $conversation
    ): JsonResponse {
        Gate::authorize('view', $conversation);

        $result = $this->resumeChatStream->handle(
            conversation: $conversation,
            user: $this->user,
            decisions: $request->decisions(),
            channel: 'mobile',
        );

        return response()->json($result->payload($this->user->id, $conversation->id), 202);
    }
}
