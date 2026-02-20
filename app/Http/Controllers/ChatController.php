<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Ai\Advisor;
use App\Enums\AgentMode;
use App\Http\Requests\StoreAgentConversationRequest;
use App\Models\Conversation;
use App\Models\History;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Responses\StreamableAgentResponse;

final class ChatController
{
    public function create(
        Request $request,
        string $conversationId = ''
    ): Response {
        $conversation = $conversationId !== ''
            ? Conversation::query()->with('messages')->find($conversationId)
            : null;

        $messages = $conversation?->messages->map(fn (History $message): array => [
            'id' => $message->id,
            'role' => $message->role->value,
            'parts' => [
                ['type' => 'text', 'text' => $message->content],
            ],
        ])->all() ?? [];

        return Inertia::render('chat/create-chat', [
            'conversationId' => $conversation?->id,
            'messages' => $messages,
            'mode' => $request->enum('mode', AgentMode::class),
        ]);
    }

    public function stream(
        StoreAgentConversationRequest $request
    ): StreamableAgentResponse {
        $agent = resolve(Advisor::class, ['user' => $request->user()])
            ->withMode($request->mode())
            ->forUser($request->user());

        return $agent
            ->stream(
                prompt: $request->userMessage(),
                model: $request->modelName()->value
            )
            ->usingVercelDataProtocol();
    }
}
