<?php

declare(strict_types=1);

use App\Actions\Approvals\RejectAgentApproval;
use App\Enums\AgentApprovalStatus;
use App\Models\AgentApproval;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

covers(RejectAgentApproval::class);

it('rejects a pending approval without writing a sample', function (): void {
    $user = User::factory()->create();
    $approval = AgentApproval::factory()->create(['user_id' => $user->id]);

    $result = resolve(RejectAgentApproval::class)->handle($approval, $user);

    expect($result->status)->toBe(AgentApprovalStatus::Rejected)
        ->and($result->resolved_at)->not->toBeNull()
        ->and(HealthSyncSample::query()->count())->toBe(0);
});

it('refuses to reject a non-pending approval', function (): void {
    $user = User::factory()->create();
    $approval = AgentApproval::factory()->executed()->create(['user_id' => $user->id]);

    $result = resolve(RejectAgentApproval::class)->handle($approval, $user);

    expect($result->status)->toBe(AgentApprovalStatus::Executed);
});

it('refuses to reject an approval owned by another user', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $approval = AgentApproval::factory()->create(['user_id' => $owner->id]);

    expect(fn (): AgentApproval => resolve(RejectAgentApproval::class)->handle($approval, $intruder))
        ->toThrow(AuthorizationException::class);

    expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Pending);
});
