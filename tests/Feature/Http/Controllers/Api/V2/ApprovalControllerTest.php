<?php

declare(strict_types=1);

use App\Enums\AgentApprovalStatus;
use App\Http\Controllers\Api\V2\ApprovalController;
use App\Models\AgentApproval;
use App\Models\Conversation;
use App\Models\HealthSyncSample;
use App\Models\User;

covers(ApprovalController::class);

/**
 * @return array<string, mixed>
 */
function mobileApprovalPayload(): array
{
    return [
        'log_type' => 'glucose',
        'glucose_value' => 140,
        'glucose_reading_type' => 'fasting',
        'measured_at' => now()->toIso8601String(),
    ];
}

/**
 * @return array<string, string>
 */
function mobileApprovalBearer(User $user, array $abilities = ['chat:converse']): array
{
    return ['Authorization' => 'Bearer '.$user->createToken('mobile:device-1', $abilities)->plainTextToken];
}

it('approves an approval over the api, executes it, and returns the saved card', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $approval = AgentApproval::factory()->forConversation($conversation)->create(['payload' => mobileApprovalPayload()]);

    $this->withHeaders(mobileApprovalBearer($user))
        ->postJson(route('api.v2.chat.approvals.approve', [$conversation, $approval]))
        ->assertOk()
        ->assertJson(['status' => 'executed', 'can_approve' => false]);

    expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Executed)
        ->and(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('rejects an approval over the api without writing a sample', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $approval = AgentApproval::factory()->forConversation($conversation)->create();

    $this->withHeaders(mobileApprovalBearer($user))
        ->postJson(route('api.v2.chat.approvals.reject', [$conversation, $approval]))
        ->assertOk()
        ->assertJson(['status' => 'rejected', 'can_approve' => false]);

    expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Rejected)
        ->and(HealthSyncSample::query()->count())->toBe(0);
});

it('shows the current card state over the api', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $approval = AgentApproval::factory()->forConversation($conversation)->create();

    $this->withHeaders(mobileApprovalBearer($user))
        ->getJson(route('api.v2.chat.approvals.show', [$conversation, $approval]))
        ->assertOk()
        ->assertJson(['status' => 'pending', 'can_approve' => true, 'can_reject' => true]);
});

it('executes only once under duplicate approve requests over the api', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $approval = AgentApproval::factory()->forConversation($conversation)->create(['payload' => mobileApprovalPayload()]);

    $this->withHeaders(mobileApprovalBearer($user))->postJson(route('api.v2.chat.approvals.approve', [$conversation, $approval]))->assertOk();
    $this->withHeaders(mobileApprovalBearer($user))->postJson(route('api.v2.chat.approvals.approve', [$conversation, $approval]))->assertOk();

    expect(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('forbids approving an approval owned by another user over the api', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $owner->id]);
    $approval = AgentApproval::factory()->forConversation($conversation)->create();

    $this->withHeaders(mobileApprovalBearer($intruder))
        ->postJson(route('api.v2.chat.approvals.approve', [$conversation, $approval]))
        ->assertForbidden();

    expect($approval->fresh()->status)->toBe(AgentApprovalStatus::Pending);
});

it('returns 404 when the approval does not belong to the conversation over the api', function (): void {
    $user = User::factory()->create();
    $conversationA = Conversation::factory()->create(['user_id' => $user->id]);
    $conversationB = Conversation::factory()->create(['user_id' => $user->id]);
    $approval = AgentApproval::factory()->forConversation($conversationB)->create();

    $this->withHeaders(mobileApprovalBearer($user))
        ->postJson(route('api.v2.chat.approvals.approve', [$conversationA, $approval]))
        ->assertNotFound();
});

it('requires authentication over the api', function (): void {
    $conversation = Conversation::factory()->create();
    $approval = AgentApproval::factory()->forConversation($conversation)->create();

    $this->postJson(route('api.v2.chat.approvals.approve', [$conversation, $approval]))
        ->assertUnauthorized();
});

it('requires the chat:converse ability over the api', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $approval = AgentApproval::factory()->forConversation($conversation)->create();

    $this->withHeaders(mobileApprovalBearer($user, ['sync:push']))
        ->postJson(route('api.v2.chat.approvals.approve', [$conversation, $approval]))
        ->assertForbidden();
});
