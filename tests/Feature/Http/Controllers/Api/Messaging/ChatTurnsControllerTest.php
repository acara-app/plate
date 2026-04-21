<?php

declare(strict_types=1);

use App\Contracts\ProcessesAdvisorMessage;
use App\Http\Controllers\Api\Messaging\ChatTurnsController;
use App\Models\User;
use App\Models\UserChatPlatformLink;

covers(ChatTurnsController::class);

const ALICE_CHAT_TURNS_URL = '/api/v2/messaging/platforms/mock/users/alice/chat-turns';

it('returns 409 Conflict with a fresh linking code for an unknown platform user', function (): void {
    $body = ['message' => 'hello'];

    $response = $this->withHeaders(signSidecarHeaders($body))
        ->postJson(ALICE_CHAT_TURNS_URL, $body)
        ->assertStatus(409);

    expect($response->json('linking_code'))->toBeString()->toHaveLength(8)
        ->and($response->json('expires_at'))->toBeString();

    $link = UserChatPlatformLink::forUser('mock', 'alice')->firstOrFail();
    expect($link->user_id)->toBeNull()
        ->and($link->linking_token)->toBe($response->json('linking_code'));
});

it('reuses an existing valid linking code on repeated unlinked requests', function (): void {
    $body = ['message' => 'hi'];

    $first = $this->withHeaders(signSidecarHeaders($body))
        ->postJson(ALICE_CHAT_TURNS_URL, $body);
    $second = $this->withHeaders(signSidecarHeaders($body))
        ->postJson(ALICE_CHAT_TURNS_URL, $body);

    expect($second->json('linking_code'))->toBe($first->json('linking_code'));
    expect(UserChatPlatformLink::query()->count())->toBeOne();
});

it('dispatches the advisor and returns 201 Created for a linked user', function (): void {
    $user = User::factory()->create();
    UserChatPlatformLink::query()->create([
        'user_id' => $user->id,
        'platform' => 'mock',
        'platform_user_id' => 'alice',
        'is_active' => true,
        'linked_at' => now(),
    ]);

    $advisor = Mockery::mock(ProcessesAdvisorMessage::class);
    $advisor->shouldReceive('handle')
        ->once()
        ->withArgs(fn (User $u, string $message): bool => $u->is($user) && $message === 'hello')
        ->andReturn(['response' => 'hi from advisor', 'conversation_id' => 'conv-1']);
    $this->app->instance(ProcessesAdvisorMessage::class, $advisor);

    $body = ['message' => 'hello'];

    $this->withHeaders(signSidecarHeaders($body))
        ->postJson(ALICE_CHAT_TURNS_URL, $body)
        ->assertCreated()
        ->assertJson([
            'plate_user_id' => (string) $user->id,
            'conversation_id' => 'conv-1',
            'response' => 'hi from advisor',
        ]);

    expect(UserChatPlatformLink::forUser('mock', 'alice')->first()->conversation_id)
        ->toBe('conv-1');
});

it('validates the message field', function (): void {
    $body = ['platform_message_id' => 'msg-1'];

    $this->withHeaders(signSidecarHeaders($body))
        ->postJson(ALICE_CHAT_TURNS_URL, $body)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['message']);
});
