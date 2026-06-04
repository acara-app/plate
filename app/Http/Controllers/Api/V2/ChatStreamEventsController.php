<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Actions\GetOrCreateConversationAction;
use App\Models\User;
use App\Services\StreamEventStore;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final readonly class ChatStreamEventsController
{
    public function __construct(
        private GetOrCreateConversationAction $conversationAction,
        private StreamEventStore $events,
    ) {}

    public function __invoke(Request $request, #[CurrentUser] User $user, string $conversationId): JsonResponse
    {
        $conversation = $this->conversationAction->handle($conversationId, $user);
        Gate::authorize('view', $conversation);

        $afterSequence = (int) $request->query('after', '-1');

        return response()->json([
            'streaming' => $this->events->isStreaming($conversation->id),
            'events' => $this->events->eventsAfter($conversation->id, $afterSequence),
            'lastSequence' => $this->events->lastSequence($conversation->id),
        ]);
    }
}
