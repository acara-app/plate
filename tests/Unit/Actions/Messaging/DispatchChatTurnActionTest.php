<?php

declare(strict_types=1);

use App\Actions\Messaging\DispatchChatTurnAction;
use App\Contracts\ProcessesAdvisorMessage;
use App\Enums\ChatPlatform;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Approvals\PendingApproval;

covers(DispatchChatTurnAction::class);

function advisorMock(callable $handle, ?callable $resume = null): ProcessesAdvisorMessage
{
    return new class($handle, $resume) implements ProcessesAdvisorMessage
    {
        /** @var callable */
        private $handle;

        /** @var ?callable */
        private $resumeHandler;

        public function __construct(callable $handle, ?callable $resume)
        {
            $this->handle = $handle;
            $this->resumeHandler = $resume;
        }

        public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
        {
            return ($this->handle)($user, $message, $conversationId, $attachments);
        }

        public function resume(User $user, string $conversationId, Decisions $decisions): array
        {
            return ($this->resumeHandler)($user, $conversationId, $decisions);
        }

        public function resetConversation(User $user): string
        {
            return 'reset';
        }
    };
}

function telegramLink(User $user): UserChatPlatformLink
{
    return UserChatPlatformLink::factory()->linked($user)->create([
        'platform' => ChatPlatform::Telegram,
        'conversation_id' => 'conv-1',
    ]);
}

function pendingApproval(): PendingApproval
{
    return new PendingApproval(
        id: 'call_abc',
        tool: 'log_health_entry',
        arguments: ['log_type' => 'glucose', 'summary' => 'Glucose 140 mg/dL'],
        reason: 'Glucose 140 mg/dL',
    );
}

it('sets the telegram channel context and returns approvals awaiting a decision', function (): void {
    $user = User::factory()->create();
    $link = telegramLink($user);
    $approval = pendingApproval();

    app()->instance(ProcessesAdvisorMessage::class, advisorMock(function () use ($approval): array {
        expect(Context::get('chat.channel'))->toBe('telegram');

        return ['response' => 'Please confirm', 'conversation_id' => 'conv-1', 'pending_approvals' => [$approval]];
    }));

    $result = resolve(DispatchChatTurnAction::class)->handle($link, 'log glucose 140');

    expect($result['response'])->toBe('Please confirm')
        ->and($result['pending_approvals'])->toHaveCount(1)
        ->and($result['pending_approvals'][0]->id)->toBe('call_abc');
});

it('returns an empty approvals list when the turn pauses for nothing', function (): void {
    $user = User::factory()->create();
    $link = telegramLink($user);

    app()->instance(ProcessesAdvisorMessage::class, advisorMock(fn (): array => [
        'response' => 'Hi there',
        'conversation_id' => 'conv-1',
        'pending_approvals' => [],
    ]));

    $result = resolve(DispatchChatTurnAction::class)->handle($link, 'hello');

    expect($result['pending_approvals'])->toBe([]);
});

it('resumes the paused turn with the given decisions', function (): void {
    $user = User::factory()->create();
    $link = telegramLink($user);
    $decisions = Decisions::from(['call_abc' => true]);

    app()->instance(ProcessesAdvisorMessage::class, advisorMock(
        fn (): array => ['response' => '', 'conversation_id' => 'conv-1', 'pending_approvals' => []],
        function (User $resumedUser, string $conversationId, Decisions $given) use ($user, $decisions): array {
            expect(Context::get('chat.channel'))->toBe('telegram')
                ->and($resumedUser->id)->toBe($user->id)
                ->and($conversationId)->toBe('conv-1')
                ->and($given)->toBe($decisions);

            return ['response' => 'Logged 140 mg/dL.', 'conversation_id' => 'conv-1', 'pending_approvals' => []];
        },
    ));

    $result = resolve(DispatchChatTurnAction::class)->resume($link, $decisions);

    expect($result['response'])->toBe('Logged 140 mg/dL.');
});
