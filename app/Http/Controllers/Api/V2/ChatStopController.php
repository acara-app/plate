<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Actions\GetOrCreateConversationAction;
use App\Models\User;
use App\Services\StreamEventStore;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final readonly class ChatStopController
{
    public function __construct(
        private GetOrCreateConversationAction $conversationAction,
        private StreamEventStore $events,
    ) {}

    public function __invoke(#[CurrentUser] User $user, string $conversationId): JsonResponse
    {
        $conversation = $this->conversationAction->handle($conversationId, $user);
        Gate::authorize('view', $conversation);

        $this->events->requestCancellation($conversation->id);

        return response()->json([
            'message' => 'Stream stop requested.',
            'conversationId' => $conversation->id,
        ]);
    }
}
