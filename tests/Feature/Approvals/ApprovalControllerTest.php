<?php

declare(strict_types=1);

use App\Enums\AgentApprovalStatus;
use App\Http\Controllers\ApprovalController;
use App\Models\AgentApproval;
use App\Models\Conversation;
use App\Models\HealthSyncSample;
use App\Models\User;

use function Pest\Laravel\actingAs;

covers(ApprovalController::class);

/**
 * @return array<string, mixed>
 */
function glucosePayload(): array
{
    return [
        'log_type' => 'glucose',
        'glucose_value' => 140,
        'glucose_reading_type' => 'fasting',
        'measured_at' => now()->toIso8601String(),
    ];
}

it('approves an approval, executes it, and returns the saved card', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $approval = AgentApproval::factory()->forConversation($conversation)->create(['payload' => glucosePayload()]);

    actingAs($user)
        ->postJson(route('approvals.approve', [$conversation, $approval]))
        ->assertOk()
        ->assertJson(['status' => 'executed', 'can_approve' => false]);

    expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Executed)
        ->and(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('rejects an approval without writing a sample', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $approval = AgentApproval::factory()->forConversation($conversation)->create();

    actingAs($user)
        ->postJson(route('approvals.reject', [$conversation, $approval]))
        ->assertOk()
        ->assertJson(['status' => 'rejected']);

    expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Rejected)
        ->and(HealthSyncSample::query()->count())->toBe(0);
});

it('shows the current card state', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $approval = AgentApproval::factory()->forConversation($conversation)->create();

    actingAs($user)
        ->getJson(route('approvals.show', [$conversation, $approval]))
        ->assertOk()
        ->assertJson(['status' => 'pending', 'can_approve' => true]);
});

it('executes only once under duplicate approve requests', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $approval = AgentApproval::factory()->forConversation($conversation)->create(['payload' => glucosePayload()]);

    actingAs($user)->postJson(route('approvals.approve', [$conversation, $approval]))->assertOk();
    actingAs($user)->postJson(route('approvals.approve', [$conversation, $approval]))->assertOk();

    expect(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('forbids approving an approval owned by another user', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $owner->id]);
    $approval = AgentApproval::factory()->forConversation($conversation)->create();

    actingAs($intruder)
        ->postJson(route('approvals.approve', [$conversation, $approval]))
        ->assertForbidden();

    expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Pending);
});

it('returns 404 when the approval does not belong to the conversation', function (): void {
    $user = User::factory()->create();
    $conversationA = Conversation::factory()->create(['user_id' => $user->id]);
    $conversationB = Conversation::factory()->create(['user_id' => $user->id]);
    $approval = AgentApproval::factory()->forConversation($conversationB)->create();

    actingAs($user)
        ->postJson(route('approvals.approve', [$conversationA, $approval]))
        ->assertNotFound();
});

it('requires authentication', function (): void {
    $conversation = Conversation::factory()->create();
    $approval = AgentApproval::factory()->forConversation($conversation)->create();

    $this->postJson(route('approvals.approve', [$conversation, $approval]))
        ->assertUnauthorized();
});
