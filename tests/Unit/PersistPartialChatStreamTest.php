<?php

declare(strict_types=1);

use App\Actions\PersistPartialChatStream;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Laravel\Ai\Messages\MessageRole;

covers(PersistPartialChatStream::class);

it('persists a repeated partial stream as one new conversation turn', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    $prompt = 'Give me an update';
    $streamId = 'stream_123';

    History::factory()->forConversation($conversation)->userMessage()->create([
        'content' => $prompt,
    ]);
    History::factory()->forConversation($conversation)->assistantMessage()->create([
        'content' => 'Earlier answer',
    ]);

    $attachments = [
        [
            'type' => 'image',
            'name' => null,
            'base64' => 'abc123',
            'mime' => 'image/png',
        ],
    ];
    $toolCalls = [
        [
            'id' => 'call_1',
            'name' => 'lookup_health_metric',
            'arguments' => ['metric' => 'glucose'],
            'result_id' => null,
            'reasoning_id' => null,
            'reasoning_summary' => null,
        ],
    ];
    $toolResults = [
        [
            'id' => 'call_1',
            'name' => 'lookup_health_metric',
            'arguments' => [],
            'result' => ['value' => 104],
            'result_id' => null,
        ],
    ];

    $this->travel(5)->minutes();
    $persistedAt = now();

    $action = resolve(PersistPartialChatStream::class);

    $action->handle(
        conversationId: $conversation->id,
        userId: $user->id,
        prompt: $prompt,
        attachments: $attachments,
        assistantText: 'Partial answer',
        toolCalls: $toolCalls,
        toolResults: $toolResults,
        streamId: $streamId,
    );
    $action->handle(
        conversationId: $conversation->id,
        userId: $user->id,
        prompt: $prompt,
        attachments: $attachments,
        assistantText: 'Partial answer',
        toolCalls: $toolCalls,
        toolResults: $toolResults,
        streamId: $streamId,
    );

    $messages = $conversation->fresh()->messages()->get();
    $partialMessages = $messages->slice(2)->values();
    $partialUserMessage = $partialMessages->first(fn (History $message): bool => $message->role === MessageRole::User);
    $partialAssistantMessage = $partialMessages->first(fn (History $message): bool => $message->role === MessageRole::Assistant);

    expect($messages)->toHaveCount(4)
        ->and($partialUserMessage)->not->toBeNull()
        ->and($partialUserMessage->content)->toBe($prompt)
        ->and($partialUserMessage->attachments)->toBe($attachments)
        ->and($partialAssistantMessage)->not->toBeNull()
        ->and($partialAssistantMessage->content)->toBe('Partial answer')
        ->and($partialAssistantMessage->tool_calls)->toBe($toolCalls)
        ->and($partialAssistantMessage->tool_results)->toBe($toolResults)
        ->and($conversation->fresh()->updated_at->toDateTimeString())->toBe($persistedAt->toDateTimeString());
});
