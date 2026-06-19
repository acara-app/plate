<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\ChatController;
use App\Models\Conversation;
use App\Models\ConversationSummary;
use App\Models\History;
use App\Models\User;
use Illuminate\Support\Str;

covers(ChatController::class);

/**
 * @param  array<int, string>  $abilities
 */
function chatAuthHeaders(User $user, array $abilities = ['chat:converse']): array
{
    return ['Authorization' => 'Bearer '.$user->createToken('mobile:device-1', $abilities)->plainTextToken];
}

it('deletes conversation history through the mobile API', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    History::factory()->count(2)->forConversation($conversation)->create();
    ConversationSummary::factory()->create([
        'conversation_id' => $conversation->id,
    ]);
    $token = $user->createToken('mobile:device-1', ['chat:converse'])->plainTextToken;

    $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->deleteJson(route('api.v2.chat.destroy', ['conversation' => $conversation->id]))
        ->assertOk()
        ->assertJson(['message' => 'Conversation deleted.']);

    $this->assertDatabaseMissing('agent_conversations', [
        'id' => $conversation->id,
    ]);
    $this->assertDatabaseMissing('agent_conversation_messages', [
        'conversation_id' => $conversation->id,
    ]);
    $this->assertDatabaseMissing('conversation_summaries', [
        'conversation_id' => $conversation->id,
    ]);
});

it('pins a conversation through the mobile API', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    $this->withHeaders(chatAuthHeaders($user))
        ->patchJson(route('api.v2.chat.pin', ['conversation' => $conversation->id]))
        ->assertOk()
        ->assertJson(['id' => $conversation->id, 'is_pinned' => true]);

    expect($conversation->fresh()->pinned_at)->not->toBeNull();
});

it('unpins a conversation through the mobile API', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->pinned()->create(['user_id' => $user->id]);

    $this->withHeaders(chatAuthHeaders($user))
        ->patchJson(route('api.v2.chat.unpin', ['conversation' => $conversation->id]))
        ->assertOk()
        ->assertJson(['id' => $conversation->id, 'is_pinned' => false]);

    expect($conversation->fresh()->pinned_at)->toBeNull();
});

it('lists conversations with pinned ones first and an is_pinned flag', function (): void {
    $user = User::factory()->create();
    $recent = Conversation::factory()->create([
        'user_id' => $user->id,
        'updated_at' => now(),
    ]);
    $pinned = Conversation::factory()->pinned()->create([
        'user_id' => $user->id,
        'updated_at' => now()->subDays(2),
    ]);

    $this->withHeaders(chatAuthHeaders($user))
        ->getJson(route('api.v2.chat.index'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $pinned->id)
        ->assertJsonPath('data.0.is_pinned', true)
        ->assertJsonPath('data.1.id', $recent->id)
        ->assertJsonPath('data.1.is_pinned', false);
});

it('forbids pinning another user\'s conversation', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $owner->id]);

    $this->withHeaders(chatAuthHeaders($other))
        ->patchJson(route('api.v2.chat.pin', ['conversation' => $conversation->id]))
        ->assertForbidden();

    expect($conversation->fresh()->pinned_at)->toBeNull();
});

it('returns 404 when pinning a missing conversation', function (): void {
    $user = User::factory()->create();

    $this->withHeaders(chatAuthHeaders($user))
        ->patchJson(route('api.v2.chat.pin', ['conversation' => (string) Str::uuid7()]))
        ->assertNotFound();
});
