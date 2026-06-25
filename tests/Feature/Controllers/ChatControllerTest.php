<?php

declare(strict_types=1);

use App\Http\Controllers\BroadcastChatController;
use App\Http\Controllers\ChatController;
use App\Models\AgentApproval;
use App\Models\AiUsage;
use App\Models\Conversation;
use App\Models\ConversationSummary;
use App\Models\History;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

covers(ChatController::class, BroadcastChatController::class);

beforeEach(function (): void {
    //
});

it('renders chat page with correct props when no conversation id provided', function (): void {
    $user = User::factory()->create();
    $conversationId = (string) fake()->uuid();

    actingAs($user)
        ->get(route('chat.create', ['conversationId' => $conversationId]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('conversationId', $conversationId)
            ->has('messages', 0)
            ->missing('mode')
        );
});

it('renders chat page with correct props with conversation id', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $history = History::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'Hello',
    ]);

    actingAs($user)
        ->get(route('chat.create', ['conversationId' => $conversation->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('conversationId', $conversation->id)
            ->has('messages', 1)
            ->where('messages.0.id', $history->id)
            ->where('messages.0.role', 'user')
            ->where('messages.0.parts.0.text', 'Hello')
        );
});

it('passes an optional initial prompt to the chat page', function (): void {
    $user = User::factory()->create();
    $conversationId = (string) fake()->uuid();

    actingAs($user)
        ->get(route('chat.create', [
            'conversationId' => $conversationId,
            'prompt' => 'Create a 7-day meal plan',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('conversationId', $conversationId)
            ->where('initialPrompt', 'Create a 7-day meal plan')
        );
});

it('returns 400 for invalid UUID format', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('chat.create', ['conversationId' => 'not-a-uuid']))
        ->assertStatus(400);
});

it('prevents access to another users conversation', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $owner->id]);

    actingAs($intruder)
        ->get(route('chat.create', ['conversationId' => $conversation->id]))
        ->assertForbidden();
});

it('deletes a conversation history owned by the authenticated user', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    History::factory()->count(2)->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
    ]);
    ConversationSummary::factory()->create([
        'conversation_id' => $conversation->id,
    ]);
    AgentApproval::factory()->forConversation($conversation)->create();
    $link = UserChatPlatformLink::factory()->linked($user)->create([
        'conversation_id' => $conversation->id,
    ]);

    actingAs($user)
        ->delete(route('chat.destroy', ['conversation' => $conversation->id]))
        ->assertRedirect(route('chat.index'));

    $this->assertDatabaseMissing('agent_conversations', [
        'id' => $conversation->id,
    ]);
    $this->assertDatabaseMissing('agent_conversation_messages', [
        'conversation_id' => $conversation->id,
    ]);
    $this->assertDatabaseMissing('conversation_summaries', [
        'conversation_id' => $conversation->id,
    ]);
    $this->assertDatabaseMissing('agent_approvals', [
        'conversation_id' => $conversation->id,
    ]);

    expect($link->fresh()->conversation_id)->toBeNull();
});

it('prevents deleting another users conversation history', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $owner->id]);
    History::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $owner->id,
    ]);

    actingAs($intruder)
        ->delete(route('chat.destroy', ['conversation' => $conversation->id]))
        ->assertForbidden();

    $this->assertDatabaseHas('agent_conversations', [
        'id' => $conversation->id,
    ]);
    $this->assertDatabaseHas('agent_conversation_messages', [
        'conversation_id' => $conversation->id,
    ]);
});

it('validates stream endpoint', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->post(route('chat.stream', $conversation->id), [])
        ->assertSessionHasErrors(['messages'])
        ->assertSessionDoesntHaveErrors(['mode'])
        ->assertSessionDoesntHaveErrors(['model']);
});

it('accepts valid stream request', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->post(route('chat.stream', $conversation->id), [
            'messages' => [
                ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello API']]],
            ],
        ])
        ->assertAccepted();
});

it('prevents cross-user access on the stream endpoint', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $owner->id]);

    actingAs($intruder)
        ->post(route('chat.stream', $conversation->id), [
            'messages' => [
                ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Leak me their history']]],
            ],
        ])
        ->assertForbidden();
});

it('returns the credit warning derived from current usage when over 80%', function (): void {
    Config::set('plate.enable_premium_upgrades', true);

    $user = User::factory()->create();
    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.34,
    ]);

    $conversationId = (string) fake()->uuid();

    actingAs($user)
        ->get(route('chat.create', ['conversationId' => $conversationId]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('creditWarning.limit_type', 'rolling')
            ->where('creditWarning.percentage', 85)
            ->where('creditWarning.current_credits', 340)
            ->where('creditWarning.limit_credits', 400)
        );
});

it('returns a credit warning capped at 100% when usage is over the cap', function (): void {
    Config::set('plate.enable_premium_upgrades', true);

    $user = User::factory()->create();
    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.50,
    ]);

    $conversationId = (string) fake()->uuid();

    actingAs($user)
        ->get(route('chat.create', ['conversationId' => $conversationId]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('creditWarning.limit_type', 'rolling')
            ->where('creditWarning.percentage', 100)
            ->where('creditWarning.current_credits', 500)
        );
});

it('returns null creditWarning when usage is below 80%', function (): void {
    Config::set('plate.enable_premium_upgrades', true);

    $user = User::factory()->create();
    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.05,
    ]);

    $conversationId = (string) fake()->uuid();

    actingAs($user)
        ->get(route('chat.create', ['conversationId' => $conversationId]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('creditWarning', null)
        );
});

it('includes image attachments in message parts when loading conversation', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    $base64Content = base64_encode('fake-image-data');
    $history = History::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'What is this food?',
        'attachments' => [
            ['type' => 'base64-image', 'name' => null, 'base64' => $base64Content, 'mime' => 'image/jpeg'],
        ],
    ]);

    actingAs($user)
        ->get(route('chat.create', ['conversationId' => $conversation->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('conversationId', $conversation->id)
            ->has('messages', 1)
            ->where('messages.0.id', $history->id)
            ->where('messages.0.parts.0.type', 'text')
            ->where('messages.0.parts.0.text', 'What is this food?')
            ->where('messages.0.parts.1.type', 'file')
            ->where('messages.0.parts.1.mediaType', 'image/jpeg')
            ->where('messages.0.parts.1.url', 'data:image/jpeg;base64,'.$base64Content)
        );
});

it('pins a conversation owned by the authenticated user', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->from(route('chat.index'))
        ->patch(route('chat.pin', ['conversation' => $conversation->id]))
        ->assertRedirect(route('chat.index'))
        ->assertInertiaFlash('toast', [
            'message' => __('common.conversations.pinned_toast'),
            'type' => 'success',
        ]);

    expect($conversation->fresh()->pinned_at)->not->toBeNull();
});

it('unpins a conversation and refreshes its activity timestamp', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->pinned()->stale(5)->create(['user_id' => $user->id]);

    actingAs($user)
        ->from(route('chat.index'))
        ->patch(route('chat.unpin', ['conversation' => $conversation->id]))
        ->assertRedirect(route('chat.index'))
        ->assertInertiaFlash('toast', [
            'message' => __('common.conversations.unpinned_toast'),
            'type' => 'success',
        ]);

    $fresh = $conversation->fresh();

    expect($fresh->pinned_at)->toBeNull()
        ->and($fresh->updated_at->isAfter(now()->subMinute()))->toBeTrue();
});

it('prevents pinning another users conversation', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $owner->id]);

    actingAs($intruder)
        ->patch(route('chat.pin', ['conversation' => $conversation->id]))
        ->assertForbidden();

    expect($conversation->fresh()->pinned_at)->toBeNull();
});

it('keeps a conversation owned by the authenticated user', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->from(route('chat.index'))
        ->patch(route('chat.keep', ['conversation' => $conversation->id]))
        ->assertRedirect(route('chat.index'))
        ->assertInertiaFlash('toast', [
            'message' => __('common.conversations.kept_toast'),
            'type' => 'success',
        ]);

    expect($conversation->fresh()->kept_at)->not->toBeNull();
});

it('unkeeps a conversation and refreshes its activity timestamp', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->kept()->stale(5)->create(['user_id' => $user->id]);

    actingAs($user)
        ->from(route('chat.index'))
        ->patch(route('chat.unkeep', ['conversation' => $conversation->id]))
        ->assertRedirect(route('chat.index'))
        ->assertInertiaFlash('toast', [
            'message' => __('common.conversations.unkept_toast'),
            'type' => 'success',
        ]);

    $fresh = $conversation->fresh();

    expect($fresh->kept_at)->toBeNull()
        ->and($fresh->updated_at->isAfter(now()->subMinute()))->toBeTrue();
});

it('prevents keeping another users conversation', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $owner->id]);

    actingAs($intruder)
        ->patch(route('chat.keep', ['conversation' => $conversation->id]))
        ->assertForbidden();

    expect($conversation->fresh()->kept_at)->toBeNull();
});
