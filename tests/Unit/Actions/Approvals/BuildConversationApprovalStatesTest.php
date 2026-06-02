<?php

declare(strict_types=1);

use App\Actions\Approvals\BuildConversationApprovalStates;
use App\Models\AgentApproval;
use App\Models\Conversation;
use App\Models\User;

covers(BuildConversationApprovalStates::class);

it('maps approval id to live card for the conversation only', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    $pending = AgentApproval::factory()->forConversation($conversation)->create();
    $executed = AgentApproval::factory()->forConversation($conversation)->executed()->create();
    $otherConversation = AgentApproval::factory()->create();

    $states = resolve(BuildConversationApprovalStates::class)->handle($conversation);

    expect($states)->toHaveKeys([$pending->id, $executed->id])
        ->and($states)->not->toHaveKey($otherConversation->id)
        ->and($states[$pending->id]['status'])->toBe('pending')
        ->and($states[$pending->id]['summary'])->toBe('Glucose 140 mg/dL (fasting)')
        ->and($states[$pending->id]['can_approve'])->toBeTrue()
        ->and($states[$executed->id]['status'])->toBe('executed');
});

it('returns an empty map for a conversation with no approvals', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    expect(resolve(BuildConversationApprovalStates::class)->handle($conversation))->toBe([]);
});
