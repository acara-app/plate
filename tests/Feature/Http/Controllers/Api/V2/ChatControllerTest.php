<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\ChatController;
use App\Models\Conversation;
use App\Models\ConversationSummary;
use App\Models\History;
use App\Models\User;

covers(ChatController::class);

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
