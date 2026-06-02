<?php

declare(strict_types=1);

use App\Enums\AgentApprovalStatus;

covers(AgentApprovalStatus::class);

it('only allows approve and reject while pending', function (AgentApprovalStatus $status, bool $expected): void {
    expect($status->canApprove())->toBe($expected)
        ->and($status->canReject())->toBe($expected);
})->with([
    'pending' => [AgentApprovalStatus::Pending, true],
    'approved' => [AgentApprovalStatus::Approved, false],
    'executing' => [AgentApprovalStatus::Executing, false],
    'executed' => [AgentApprovalStatus::Executed, false],
    'failed' => [AgentApprovalStatus::Failed, false],
    'rejected' => [AgentApprovalStatus::Rejected, false],
    'expired' => [AgentApprovalStatus::Expired, false],
]);

it('reports in-flight statuses', function (AgentApprovalStatus $status, bool $expected): void {
    expect($status->isInFlight())->toBe($expected);
})->with([
    'pending' => [AgentApprovalStatus::Pending, false],
    'approved' => [AgentApprovalStatus::Approved, true],
    'executing' => [AgentApprovalStatus::Executing, true],
    'executed' => [AgentApprovalStatus::Executed, false],
    'rejected' => [AgentApprovalStatus::Rejected, false],
]);

it('reports terminal statuses', function (AgentApprovalStatus $status, bool $expected): void {
    expect($status->isTerminal())->toBe($expected);
})->with([
    'pending' => [AgentApprovalStatus::Pending, false],
    'approved' => [AgentApprovalStatus::Approved, false],
    'executing' => [AgentApprovalStatus::Executing, false],
    'executed' => [AgentApprovalStatus::Executed, true],
    'failed' => [AgentApprovalStatus::Failed, true],
    'rejected' => [AgentApprovalStatus::Rejected, true],
    'expired' => [AgentApprovalStatus::Expired, true],
]);
