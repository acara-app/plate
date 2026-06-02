<?php

declare(strict_types=1);

use App\Actions\Approvals\ApproveAgentApproval;
use App\Enums\AgentApprovalStatus;
use App\Jobs\ExecuteApprovalJob;
use App\Models\AgentApproval;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Queue;

covers(ApproveAgentApproval::class);

it('approves a pending approval and dispatches the execution job once', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $approval = AgentApproval::factory()->create(['user_id' => $user->id]);

    $result = resolve(ApproveAgentApproval::class)->handle($approval, $user);

    expect($result->status)->toBe(AgentApprovalStatus::Approved)
        ->and($result->resolved_at)->not->toBeNull();

    Queue::assertPushed(ExecuteApprovalJob::class, 1);
});

it('is idempotent under duplicate approvals', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $approval = AgentApproval::factory()->create(['user_id' => $user->id]);

    $action = resolve(ApproveAgentApproval::class);
    $action->handle($approval, $user);
    $action->handle($approval->fresh(), $user);

    Queue::assertPushed(ExecuteApprovalJob::class, 1);
});

it('does not dispatch when the approval is not pending', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $approval = AgentApproval::factory()->executed()->create(['user_id' => $user->id]);

    $result = resolve(ApproveAgentApproval::class)->handle($approval, $user);

    expect($result->status)->toBe(AgentApprovalStatus::Executed);

    Queue::assertNothingPushed();
});

it('refuses to approve an approval owned by another user', function (): void {
    Queue::fake();

    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $approval = AgentApproval::factory()->create(['user_id' => $owner->id]);

    expect(fn (): AgentApproval => resolve(ApproveAgentApproval::class)->handle($approval, $intruder))
        ->toThrow(AuthorizationException::class);

    Queue::assertNothingPushed();
    expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Pending);
});

it('executes end-to-end through the queued job on the sync connection', function (): void {
    $user = User::factory()->create();
    $approval = AgentApproval::factory()->create([
        'user_id' => $user->id,
        'payload' => [
            'log_type' => 'glucose',
            'glucose_value' => 140,
            'glucose_reading_type' => 'fasting',
            'measured_at' => now()->toIso8601String(),
        ],
    ]);

    $result = resolve(ApproveAgentApproval::class)->handle($approval, $user);

    expect($result->status)->toBe(AgentApprovalStatus::Executed)
        ->and($result->result)->toHaveKey('entry_id')
        ->and(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(1);
});
