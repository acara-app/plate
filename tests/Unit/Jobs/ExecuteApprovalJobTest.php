<?php

declare(strict_types=1);

use App\Enums\AgentApprovalStatus;
use App\Events\AgentApprovalResolved;
use App\Jobs\ExecuteApprovalJob;
use App\Models\AgentApproval;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Support\Facades\Event;

covers(ExecuteApprovalJob::class);

it('executes an approved approval exactly once and records the result', function (): void {
    $user = User::factory()->create();
    $approval = AgentApproval::factory()->approved()->create([
        'user_id' => $user->id,
        'payload' => [
            'log_type' => 'glucose',
            'glucose_value' => 140,
            'glucose_reading_type' => 'fasting',
            'measured_at' => now()->toIso8601String(),
        ],
    ]);

    new ExecuteApprovalJob($approval->id)->handle();

    $approval->refresh();

    expect($approval->status)->toBe(AgentApprovalStatus::Executed)
        ->and($approval->result)->toHaveKey('entry_id')
        ->and($approval->executed_at)->not->toBeNull()
        ->and(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(1);

    new ExecuteApprovalJob($approval->id)->handle();

    expect(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and($approval->fresh()->status)->toBe(AgentApprovalStatus::Executed);
});

it('does nothing when the approval is not approved', function (): void {
    $user = User::factory()->create();
    $approval = AgentApproval::factory()->create(['user_id' => $user->id]);

    new ExecuteApprovalJob($approval->id)->handle();

    expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Pending)
        ->and(HealthSyncSample::query()->count())->toBe(0);
});

it('marks the approval failed when the executor throws and writes no sample', function (): void {
    $user = User::factory()->create();
    $approval = AgentApproval::factory()->approved()->create([
        'user_id' => $user->id,
        'payload' => ['log_type' => 'vitals', 'measured_at' => now()->toIso8601String()],
    ]);

    new ExecuteApprovalJob($approval->id)->handle();

    $approval->refresh();

    expect($approval->status)->toBe(AgentApprovalStatus::Failed)
        ->and($approval->error)->not->toBeNull()
        ->and(HealthSyncSample::query()->count())->toBe(0);
});

it('fails when no executor is registered for the tool', function (): void {
    $user = User::factory()->create();
    $approval = AgentApproval::factory()->approved()->create([
        'user_id' => $user->id,
        'tool_name' => 'unregistered_tool',
    ]);

    new ExecuteApprovalJob($approval->id)->handle();

    expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Failed)
        ->and($approval->fresh()->error)->toContain('No executor registered');
});

it('marks an in-flight approval failed when the job fails', function (): void {
    $approval = AgentApproval::factory()->approved()->create();

    new ExecuteApprovalJob($approval->id)->failed(new RuntimeException('worker crashed'));

    expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Failed)
        ->and($approval->fresh()->error)->toBe('worker crashed');
});

it('leaves a terminal approval untouched when the job fails late', function (): void {
    $approval = AgentApproval::factory()->executed()->create();

    new ExecuteApprovalJob($approval->id)->failed(new RuntimeException('late failure'));

    expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Executed);
});

it('fires the resolved event after a successful execution', function (): void {
    Event::fake([AgentApprovalResolved::class]);

    $approval = AgentApproval::factory()->approved()->create([
        'payload' => [
            'log_type' => 'glucose',
            'glucose_value' => 140,
            'glucose_reading_type' => 'fasting',
            'measured_at' => now()->toIso8601String(),
        ],
    ]);

    new ExecuteApprovalJob($approval->id)->handle();

    Event::assertDispatched(AgentApprovalResolved::class, fn (AgentApprovalResolved $event): bool => $event->approvalId === $approval->id);
});

it('fires the resolved event after a failed execution', function (): void {
    Event::fake([AgentApprovalResolved::class]);

    $approval = AgentApproval::factory()->approved()->create([
        'payload' => ['log_type' => 'vitals', 'measured_at' => now()->toIso8601String()],
    ]);

    new ExecuteApprovalJob($approval->id)->handle();

    expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Failed);
    Event::assertDispatched(AgentApprovalResolved::class);
});
