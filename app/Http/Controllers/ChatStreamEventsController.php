<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\StreamEventStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final readonly class ChatStreamEventsController
{
    public function __construct(
        private StreamEventStore $events,
    ) {}

    public function __invoke(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $afterSequence = (int) $request->query('after', '-1');
        $events = $this->events->eventsAfter($conversation->id, $afterSequence);

        return response()->json([
            'streaming' => $this->events->isStreaming($conversation->id),
            'events' => $events,
            'lastSequence' => $this->events->lastSequence($conversation->id),
        ]);
    }
}
