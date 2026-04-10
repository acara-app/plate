<?php

declare(strict_types=1);

use App\Actions\BuildConversationMessagesAction;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;

covers(BuildConversationMessagesAction::class);

beforeEach(function (): void {
    $this->action = resolve(BuildConversationMessagesAction::class);
    $this->user = User::factory()->create();
    $this->conversation = Conversation::factory()->create(['user_id' => $this->user->id]);
});

it('returns empty array when conversation is null', function (): void {
    expect($this->action->handle(null))->toBe([]);
});

it('returns empty array when conversation has no messages', function (): void {
    $this->conversation->load('messages');

    expect($this->action->handle($this->conversation))->toBe([]);
});

it('maps a text-only history message to the correct structure', function (): void {
    $history = History::factory()->create([
        'conversation_id' => $this->conversation->id,
        'role' => 'user',
        'content' => 'Hello world',
    ]);

    $this->conversation->load('messages');
    $result = $this->action->handle($this->conversation);

    expect($result)->toHaveCount(1)
        ->and($result[0]['id'])->toBe($history->id)
        ->and($result[0]['role'])->toBe('user')
        ->and($result[0]['parts'])->toHaveCount(1)
        ->and($result[0]['parts'][0])->toBe(['type' => 'text', 'text' => 'Hello world']);
});

it('maps assistant role correctly', function (): void {
    History::factory()->create([
        'conversation_id' => $this->conversation->id,
        'role' => 'assistant',
        'content' => 'How can I help?',
    ]);

    $this->conversation->load('messages');
    $result = $this->action->handle($this->conversation);

    expect($result[0]['role'])->toBe('assistant')
        ->and($result[0]['parts'][0]['text'])->toBe('How can I help?');
});

it('maps image attachments as file parts after the text part', function (): void {
    $base64 = base64_encode('fake-image-data');

    History::factory()->create([
        'conversation_id' => $this->conversation->id,
        'role' => 'user',
        'content' => 'What food is this?',
        'attachments' => [
            ['type' => 'base64-image', 'mime' => 'image/png', 'base64' => $base64],
        ],
    ]);

    $this->conversation->load('messages');
    $result = $this->action->handle($this->conversation);

    expect($result[0]['parts'])->toHaveCount(2)
        ->and($result[0]['parts'][0])->toBe(['type' => 'text', 'text' => 'What food is this?'])
        ->and($result[0]['parts'][1])->toBe([
            'type' => 'file',
            'mediaType' => 'image/png',
            'url' => 'data:image/png;base64,'.$base64,
        ]);
});

it('falls back to image/jpeg mime when attachment mime is missing', function (): void {
    $base64 = base64_encode('raw-bytes');

    History::factory()->create([
        'conversation_id' => $this->conversation->id,
        'role' => 'user',
        'content' => 'Look at this',
        'attachments' => [
            ['base64' => $base64],
        ],
    ]);

    $this->conversation->load('messages');
    $result = $this->action->handle($this->conversation);

    expect($result[0]['parts'][1]['mediaType'])->toBe('image/jpeg')
        ->and($result[0]['parts'][1]['url'])->toBe('data:image/jpeg;base64,'.$base64);
});

it('falls back to empty string when attachment base64 is missing', function (): void {
    History::factory()->create([
        'conversation_id' => $this->conversation->id,
        'role' => 'user',
        'content' => 'Blank attachment',
        'attachments' => [
            ['mime' => 'image/gif'],
        ],
    ]);

    $this->conversation->load('messages');
    $result = $this->action->handle($this->conversation);

    expect($result[0]['parts'][1]['url'])->toBe('data:image/gif;base64,');
});

it('maps multiple messages in order', function (): void {
    History::factory()->create([
        'conversation_id' => $this->conversation->id,
        'role' => 'user',
        'content' => 'First message',
    ]);
    History::factory()->create([
        'conversation_id' => $this->conversation->id,
        'role' => 'assistant',
        'content' => 'Second message',
    ]);

    $this->conversation->load('messages');
    $result = $this->action->handle($this->conversation);

    expect($result)->toHaveCount(2)
        ->and($result[0]['parts'][0]['text'])->toBe('First message')
        ->and($result[1]['parts'][0]['text'])->toBe('Second message');
});
