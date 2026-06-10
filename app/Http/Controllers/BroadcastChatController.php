<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StartChatStream;
use App\Http\Requests\StreamChatRequest;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final readonly class BroadcastChatController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private StartChatStream $startChatStream,
    ) {}

    public function __invoke(
        StreamChatRequest $request,
        Conversation $conversation
    ): JsonResponse {
        Gate::authorize('view', $conversation);

        $turn = $this->startChatStream->handle($request, $this->user, $conversation);

        return response()->json($turn->acceptedPayload($this->user->id, $conversation->id), 202);
    }
}
