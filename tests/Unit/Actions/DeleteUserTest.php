<?php

declare(strict_types=1);

use App\Actions\DeleteUser;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;

covers(DeleteUser::class);

it('deletes a user and creates a deleted user record', function (): void {
    $user = User::factory()->create();

    resolve(DeleteUser::class)->handle($user);

    expect($user->exists)->toBeFalse();

    $this->assertDatabaseHas('deleted_users', [
        'user_id' => $user->id,
        'email' => $user->email,
    ]);
});

it('preserves orphaned conversations after user deletion', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    History::factory()->forConversation($conversation)->create();

    resolve(DeleteUser::class)->handle($user);

    $this->assertDatabaseHas('agent_conversations', ['id' => $conversation->id]);
    $this->assertDatabaseHas('agent_conversation_messages', ['conversation_id' => $conversation->id]);
});

it('does not affect other users', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    resolve(DeleteUser::class)->handle($user);

    expect($otherUser->fresh())->not->toBeNull();
    $this->assertDatabaseMissing('deleted_users', ['user_id' => $otherUser->id]);
});
