<?php

declare(strict_types=1);

use App\Console\Commands\ExpireStaleAgentApprovalsCommand;
use App\Enums\AgentApprovalStatus;
use App\Models\AgentApproval;

covers(ExpireStaleAgentApprovalsCommand::class);

it('expires only pending approvals that are past their expiry', function (): void {
    $stale = AgentApproval::factory()->stale()->create();
    $fresh = AgentApproval::factory()->create();
    $approved = AgentApproval::factory()->approved()->create(['expires_at' => now()->subDay()]);
    $executed = AgentApproval::factory()->executed()->create(['expires_at' => now()->subDay()]);

    $this->artisan('approvals:expire-stale')->assertSuccessful();

    expect($stale->fresh()->status)->toBe(AgentApprovalStatus::Expired)
        ->and($stale->fresh()->resolved_at)->not->toBeNull()
        ->and($fresh->fresh()->status)->toBe(AgentApprovalStatus::Pending)
        ->and($approved->fresh()->status)->toBe(AgentApprovalStatus::Approved)
        ->and($executed->fresh()->status)->toBe(AgentApprovalStatus::Executed);
});
