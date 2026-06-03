<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\Requests\StreamChatRequest;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class BuildAssistantAgentAction
{
    public function __construct(
        private StartAgentStreamRunAction $startRun,
        private ReplayAgentStreamAction $replay,
    ) {}

    public function handle(StreamChatRequest $request, User $user, string $conversationId, string $channel = 'web'): StreamedResponse
    {
        $run = $this->startRun->handle($request, $user, $conversationId, $channel);

        return $this->replay->handle($run->id);
    }
}
