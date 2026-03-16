<?php

declare(strict_types=1);

use App\Actions\BuildConversationMessagesAction;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;

beforeEach(function (): void {
    $this->action = resolve(BuildConversationMessagesAction::class);
});

it('returns empty array when conversation is null', function (): void {
    $result = $this->action->handle(null);

    expect($result)->toBe([]);
});

it('returns empty array when conversation has no messages', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    $conversation->load('messages');

    $result = $this->action->handle($conversation);

    expect($result)->toBe([]);
});

it('maps a text-only history message to the correct structure', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $history = History::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'Hello world',
    ]);

    $conversation->load('messages');

    $result = $this->action->handle($conversation);

    expect($result)->toHaveCount(1);
    expect($result[0]['id'])->toBe($history->id);
    expect($result[0]['role'])->toBe('user');
    expect($result[0]['parts'])->toHaveCount(1);
    expect($result[0]['parts'][0])->toBe(['type' => 'text', 'text' => 'Hello world']);
});

it('maps assistant role correctly', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    History::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'assistant',
        'content' => 'How can I help?',
    ]);

    $conversation->load('messages');

    $result = $this->action->handle($conversation);

    expect($result[0]['role'])->toBe('assistant');
    expect($result[0]['parts'][0]['text'])->toBe('How can I help?');
});

it('maps image attachments as file parts after the text part', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $base64 = base64_encode('fake-image-data');

    History::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'What food is this?',
        'attachments' => [
            ['type' => 'base64-image', 'mime' => 'image/png', 'base64' => $base64],
        ],
    ]);

    $conversation->load('messages');

    $result = $this->action->handle($conversation);

    expect($result[0]['parts'])->toHaveCount(2);
    expect($result[0]['parts'][0])->toBe(['type' => 'text', 'text' => 'What food is this?']);
    expect($result[0]['parts'][1])->toBe([
        'type' => 'file',
        'mediaType' => 'image/png',
        'url' => 'data:image/png;base64,'.$base64,
    ]);
});

it('falls back to image/jpeg mime when attachment mime is missing', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $base64 = base64_encode('raw-bytes');

    History::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'Look at this',
        'attachments' => [
            ['base64' => $base64],
        ],
    ]);

    $conversation->load('messages');

    $result = $this->action->handle($conversation);

    expect($result[0]['parts'][1]['mediaType'])->toBe('image/jpeg');
    expect($result[0]['parts'][1]['url'])->toBe('data:image/jpeg;base64,'.$base64);
});

it('falls back to empty string when attachment base64 is missing', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    History::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'Blank attachment',
        'attachments' => [
            ['mime' => 'image/gif'],
        ],
    ]);

    $conversation->load('messages');

    $result = $this->action->handle($conversation);

    expect($result[0]['parts'][1]['url'])->toBe('data:image/gif;base64,');
});

it('maps multiple messages in order', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    History::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'First message',
    ]);
    History::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'assistant',
        'content' => 'Second message',
    ]);

    $conversation->load('messages');

    $result = $this->action->handle($conversation);

    expect($result)->toHaveCount(2);
    expect($result[0]['parts'][0]['text'])->toBe('First message');
    expect($result[1]['parts'][0]['text'])->toBe('Second message');
});
