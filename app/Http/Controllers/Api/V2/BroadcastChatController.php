<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Actions\GetOrCreateConversationAction;
use App\Actions\StartChatStream;
use App\Http\Requests\Api\V2\ChatStreamRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final readonly class BroadcastChatController
{
    public function __construct(
        private GetOrCreateConversationAction $conversationAction,
        private StartChatStream $startChatStream,
    ) {}

    public function __invoke(
        ChatStreamRequest $request,
        #[CurrentUser] User $user,
        string $conversationId
    ): JsonResponse {
        $conversation = $this->conversationAction->handle($conversationId, $user);
        Gate::authorize('view', $conversation);

        return $this->startChatStream->handle($request, $user, $conversation, 'mobile');
    }
}
