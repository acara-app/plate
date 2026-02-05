<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Agents\NutritionAdvisor;
use App\Http\Requests\StoreAgentConversationRequest;
use Inertia\Inertia;
use Laravel\Ai\Responses\StreamableAgentResponse;

final class ChatController
{
    public function create(): \Inertia\Response
    {
        return Inertia::render('chat/create-chat');
    }

    public function stream(
        StoreAgentConversationRequest $request
    ): StreamableAgentResponse {
        return (new NutritionAdvisor(user: $request->user()))
            ->forUser($request->user())
            ->stream($request->userMessage())
            ->usingVercelDataProtocol();
    }
}
