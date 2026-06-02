<?php

declare(strict_types=1);

use App\Actions\Approvals\CreateAgentApproval;
use App\Enums\AgentApprovalStatus;
use App\Models\Conversation;
use App\Models\User;

covers(CreateAgentApproval::class);

it('creates a pending approval with an encrypted payload and a per-tool ttl', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    config()->set('plate.approvals.ttl_hours', ['default' => 24, 'log_health_entry' => 6]);

    $payload = ['log_type' => 'glucose', 'glucose_value' => 140, 'measured_at' => now()->toIso8601String()];

    $approval = resolve(CreateAgentApproval::class)->handle('log_health_entry', $payload, 'Glucose 140 mg/dL (fasting)', $conversation, $user);

    expect($approval->status)->toBe(AgentApprovalStatus::Pending)
        ->and($approval->tool_name)->toBe('log_health_entry')
        ->and($approval->conversation_id)->toBe($conversation->id)
        ->and($approval->user_id)->toBe($user->id)
        ->and($approval->payload)->toBe($payload)
        ->and($approval->summary)->toBe('Glucose 140 mg/dL (fasting)')
        ->and($approval->expires_at->toDateTimeString())->toBe(now()->addHours(6)->toDateTimeString());

    $this->assertDatabaseHas('agent_approvals', [
        'id' => $approval->id,
        'status' => 'pending',
        'tool_name' => 'log_health_entry',
    ]);
});

it('persists the originating channel', function (): void {
    $user = User::factory()->create();

    $approval = resolve(CreateAgentApproval::class)->handle('log_health_entry', ['x' => 1], 'Summary', null, $user, 'telegram');

    expect($approval->channel)->toBe('telegram');

    $this->assertDatabaseHas('agent_approvals', [
        'id' => $approval->id,
        'channel' => 'telegram',
    ]);
});

it('leaves the channel null when none is provided', function (): void {
    $user = User::factory()->create();

    $approval = resolve(CreateAgentApproval::class)->handle('log_health_entry', ['x' => 1], 'Summary', null, $user);

    expect($approval->channel)->toBeNull();
});

it('falls back to the default ttl and a null conversation', function (): void {
    $user = User::factory()->create();

    config()->set('plate.approvals.ttl_hours', ['default' => 12]);

    $approval = resolve(CreateAgentApproval::class)->handle('some_other_tool', ['x' => 1], 'Do the thing', null, $user);

    expect($approval->expires_at->toDateTimeString())->toBe(now()->addHours(12)->toDateTimeString())
        ->and($approval->conversation_id)->toBeNull();
});
