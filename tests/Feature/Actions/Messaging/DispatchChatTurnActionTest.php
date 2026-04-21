<?php

declare(strict_types=1);

use App\Actions\Messaging\DispatchChatTurnAction;
use App\Contracts\ProcessesAdvisorMessage;
use App\Models\User;
use App\Models\UserChatPlatformLink;

it('delegates to the advisor and persists the conversation id', function (): void {
    $user = User::factory()->create();
    $link = UserChatPlatformLink::factory()->linked($user)->create(['conversation_id' => null]);

    $advisor = Mockery::mock(ProcessesAdvisorMessage::class);
    $advisor->shouldReceive('handle')
        ->once()
        ->withArgs(fn (User $u, string $m, ?string $cid, array $atts): bool => $u->is($user) && $m === 'hi' && $cid === null && $atts === [])
        ->andReturn(['response' => 'hello!', 'conversation_id' => 'conv-42']);
    app()->instance(ProcessesAdvisorMessage::class, $advisor);

    $result = resolve(DispatchChatTurnAction::class)->handle($link, 'hi');

    expect($result)->toBe(['response' => 'hello!', 'conversation_id' => 'conv-42']);
    expect($link->fresh()->conversation_id)->toBe('conv-42');
});

it('does not touch conversation_id when advisor returns the same id', function (): void {
    $user = User::factory()->create();
    $link = UserChatPlatformLink::factory()->linked($user)->create(['conversation_id' => 'conv-7']);

    $advisor = Mockery::mock(ProcessesAdvisorMessage::class);
    $advisor->shouldReceive('handle')->andReturn(['response' => 'ok', 'conversation_id' => 'conv-7']);
    app()->instance(ProcessesAdvisorMessage::class, $advisor);

    $updatedAtBefore = $link->updated_at;
    resolve(DispatchChatTurnAction::class)->handle($link, 'hi');

    expect($link->fresh()->updated_at->equalTo($updatedAtBefore))->toBeTrue();
});

it('refuses to dispatch when the link has no user', function (): void {
    $link = UserChatPlatformLink::factory()->create(['user_id' => null]);

    app()->instance(ProcessesAdvisorMessage::class, Mockery::mock(ProcessesAdvisorMessage::class));

    expect(fn () => resolve(DispatchChatTurnAction::class)->handle($link, 'hi'))
        ->toThrow(LogicException::class);
});
