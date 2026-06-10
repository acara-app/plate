<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\StreamEventStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final readonly class ChatStopController
{
    public function __construct(
        private StreamEventStore $events,
    ) {}

    public function __invoke(Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $this->events->requestCancellation($conversation->id);

        return response()->json([
            'message' => 'Stream stop requested.',
            'conversationId' => $conversation->id,
        ]);
    }
}
