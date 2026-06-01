<?php

declare(strict_types=1);

use App\Actions\Messaging\DispatchChatTurnAction;
use App\Contracts\ProcessesAdvisorMessage;
use App\Enums\ChatPlatform;
use App\Models\AgentApproval;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use Illuminate\Support\Facades\Context;

covers(DispatchChatTurnAction::class);

function advisorMock(callable $handle): ProcessesAdvisorMessage
{
    return new class($handle) implements ProcessesAdvisorMessage
    {
        /** @var callable */
        private $handle;

        public function __construct(callable $handle)
        {
            $this->handle = $handle;
        }

        public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
        {
            return ($this->handle)($user, $message, $conversationId, $attachments);
        }

        public function resetConversation(User $user): string
        {
            return 'reset';
        }
    };
}

it('sets the telegram channel context and returns approvals created during the turn', function (): void {
    $user = User::factory()->create();
    $link = UserChatPlatformLink::factory()->linked($user)->create([
        'platform' => ChatPlatform::Telegram,
        'conversation_id' => 'conv-1',
    ]);

    $approval = AgentApproval::factory()->telegram()->create(['user_id' => $user->id]);

    app()->instance(ProcessesAdvisorMessage::class, advisorMock(function () use ($approval): array {
        expect(Context::get('chat.channel'))->toBe('telegram');
        Context::push('chat.created_approvals', $approval->id);

        return ['response' => 'Please confirm', 'conversation_id' => 'conv-1'];
    }));

    $result = resolve(DispatchChatTurnAction::class)->handle($link, 'log glucose 140');

    expect($result['response'])->toBe('Please confirm')
        ->and($result['pending_approvals'])->toHaveCount(1)
        ->and($result['pending_approvals'][0]->id)->toBe($approval->id);
});

it('returns an empty approvals list when the turn creates none', function (): void {
    $user = User::factory()->create();
    $link = UserChatPlatformLink::factory()->linked($user)->create([
        'platform' => ChatPlatform::Telegram,
        'conversation_id' => 'conv-1',
    ]);

    app()->instance(ProcessesAdvisorMessage::class, advisorMock(fn (): array => [
        'response' => 'Hi there',
        'conversation_id' => 'conv-1',
    ]));

    $result = resolve(DispatchChatTurnAction::class)->handle($link, 'hello');

    expect($result['pending_approvals'])->toBe([]);
});

it('never surfaces an approval owned by another user', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $link = UserChatPlatformLink::factory()->linked($user)->create([
        'platform' => ChatPlatform::Telegram,
        'conversation_id' => 'conv-1',
    ]);
    $foreign = AgentApproval::factory()->telegram()->create(['user_id' => $other->id]);

    app()->instance(ProcessesAdvisorMessage::class, advisorMock(function () use ($foreign): array {
        Context::push('chat.created_approvals', $foreign->id);

        return ['response' => 'x', 'conversation_id' => 'conv-1'];
    }));

    $result = resolve(DispatchChatTurnAction::class)->handle($link, 'hi');

    expect($result['pending_approvals'])->toBe([]);
});
