<?php

declare(strict_types=1);

use App\Enums\AgentApprovalStatus;
use App\Enums\ChatPlatform;
use App\Events\AgentApprovalResolved;
use App\Listeners\NotifyTelegramOfApprovalOutcome;
use App\Models\AgentApproval;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;

covers(NotifyTelegramOfApprovalOutcome::class);

beforeEach(function (): void {
    Telegraph::fake();

    $this->bot = TelegraphBot::factory()->create();
    $this->telegraphChat = TelegraphChat::factory()->for($this->bot, 'bot')->create(['chat_id' => '555000']);
});

function linkUserToTelegram(mixed $test, User $user): void
{
    UserChatPlatformLink::factory()->linked($user)->create([
        'platform' => ChatPlatform::Telegram,
        'platform_user_id' => (string) $test->telegraphChat->chat_id,
    ]);
}

function notify(string $approvalId): void
{
    resolve(NotifyTelegramOfApprovalOutcome::class)->handle(new AgentApprovalResolved($approvalId));
}

it('sends a saved confirmation for an executed telegram approval', function (): void {
    $user = User::factory()->create();
    linkUserToTelegram($this, $user);

    $approval = AgentApproval::factory()->telegram()->executed()->create([
        'user_id' => $user->id,
        'summary' => 'Glucose 140 mg/dL (fasting)',
    ]);

    notify($approval->id);

    Telegraph::assertSent('Saved: Glucose 140 mg/dL (fasting)', false);
});

it('sends a failure message for a failed telegram approval', function (): void {
    $user = User::factory()->create();
    linkUserToTelegram($this, $user);

    $approval = AgentApproval::factory()->telegram()->create([
        'user_id' => $user->id,
        'status' => AgentApprovalStatus::Failed,
    ]);

    notify($approval->id);

    Telegraph::assertSent('save that entry', false);
});

it('does nothing for a non-telegram approval', function (): void {
    $user = User::factory()->create();
    linkUserToTelegram($this, $user);

    $approval = AgentApproval::factory()->executed()->create([
        'user_id' => $user->id,
        'channel' => 'web',
    ]);

    notify($approval->id);

    Telegraph::assertNothingSent();
});

it('does nothing when the user has no linked telegram chat', function (): void {
    $user = User::factory()->create();

    $approval = AgentApproval::factory()->telegram()->executed()->create(['user_id' => $user->id]);

    notify($approval->id);

    Telegraph::assertNothingSent();
});

it('does nothing for a still-pending approval', function (): void {
    $user = User::factory()->create();
    linkUserToTelegram($this, $user);

    $approval = AgentApproval::factory()->telegram()->create(['user_id' => $user->id]);

    notify($approval->id);

    Telegraph::assertNothingSent();
});

it('does not send again when the approval was already notified', function (): void {
    $user = User::factory()->create();
    linkUserToTelegram($this, $user);

    $approval = AgentApproval::factory()->telegram()->executed()->create([
        'user_id' => $user->id,
        'summary' => 'Glucose 140 mg/dL (fasting)',
        'notified_at' => now(),
    ]);

    notify($approval->id);

    Telegraph::assertNothingSent();
});

it('notifies exactly once when the resolved event fires twice', function (AgentApprovalStatus $status, string $expected): void {
    $user = User::factory()->create();
    linkUserToTelegram($this, $user);

    $approval = AgentApproval::factory()->telegram()->create([
        'user_id' => $user->id,
        'status' => $status,
        'summary' => 'Glucose 140 mg/dL (fasting)',
    ]);

    notify($approval->id);

    Telegraph::assertSent($expected, false);

    $firstNotifiedAt = $approval->fresh()->notified_at;
    expect($firstNotifiedAt)->not->toBeNull();

    notify($approval->id);

    expect($approval->fresh()->notified_at->equalTo($firstNotifiedAt))->toBeTrue();
})->with([
    'executed' => [AgentApprovalStatus::Executed, 'Saved: Glucose 140 mg/dL (fasting)'],
    'failed' => [AgentApprovalStatus::Failed, 'save that entry'],
]);
