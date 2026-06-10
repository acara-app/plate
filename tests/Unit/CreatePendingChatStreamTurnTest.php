<?php

declare(strict_types=1);

use App\Actions\CreatePendingChatStreamTurn;
use App\Ai\Agents\AgentRunner;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Laravel\Ai\Messages\MessageRole;

covers(CreatePendingChatStreamTurn::class);

it('creates the user message and pending assistant message before the stream job runs', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    $turn = resolve(CreatePendingChatStreamTurn::class)->handle(
        conversation: $conversation,
        user: $user,
        prompt: 'Check my glucose pattern',
        attachments: [
            [
                'type' => 'image',
                'name' => null,
                'base64' => 'abc123',
                'mime' => 'image/png',
            ],
        ],
        channel: 'mobile',
    );

    $messages = $conversation->fresh()->messages()->get();

    expect($messages)->toHaveCount(2)
        ->and($messages[0]->id)->toBe($turn->userMessageId)
        ->and($messages[0]->agent)->toBe(AgentRunner::class)
        ->and($messages[0]->role)->toBe(MessageRole::User)
        ->and($messages[0]->content)->toBe('Check my glucose pattern')
        ->and($messages[0]->attachments[0]['mime'])->toBe('image/png')
        ->and($messages[0]->chatStreamId())->toBe($turn->streamId)
        ->and($messages[0]->chatStreamStatus())->toBe(History::STREAM_STATUS_SUBMITTED)
        ->and($messages[1]->id)->toBe($turn->assistantMessageId)
        ->and($messages[1]->role)->toBe(MessageRole::Assistant)
        ->and($messages[1]->content)->toBe('')
        ->and($messages[1]->isPendingStreamAssistant())->toBeTrue()
        ->and($messages[1]->chatStreamMeta()['user_message_id'])->toBe($turn->userMessageId);
});
