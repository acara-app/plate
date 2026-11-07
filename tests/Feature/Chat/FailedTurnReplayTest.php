<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use App\Services\Ai\PlateConversationStore;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Messages\ToolResultMessage;

it('replays the unanswered tool call of a failed turn with its call id so the conversation can continue', function (): void {
    $conversation = Conversation::factory()->forUser(User::factory()->create())->create();
    $failedTurn = History::factory()->forConversation($conversation)->create([
        'role' => MessageRole::Assistant,
        'status' => MessageStatus::Failed,
        'steps' => [['content' => '', 'tool_calls' => [['id' => 'fc_photo', 'name' => 'analyze_photo', 'arguments' => [], 'result_id' => 'call_photo']], 'reasoning' => '', 'replay_blocks' => [], 'provider_tool_calls' => []]],
    ]);

    $answer = collect(resolve(PlateConversationStore::class)->assistantTurn($failedTurn))
        ->first(fn (object $message): bool => $message instanceof ToolResultMessage);

    expect($answer->toolResults->first()->resultId)->toBe('call_photo');
});
