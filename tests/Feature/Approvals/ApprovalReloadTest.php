<?php

declare(strict_types=1);

use App\Actions\BuildConversationMessagesAction;
use App\Models\AgentApproval;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;

covers(BuildConversationMessagesAction::class);

/**
 * @return array<string, mixed>
 */
function reloadGlucosePayload(): array
{
    return [
        'log_type' => 'glucose',
        'glucose_value' => 140,
        'glucose_reading_type' => 'fasting',
        'measured_at' => now()->toIso8601String(),
    ];
}

function seedApprovalMessage(Conversation $conversation, AgentApproval $approval): void
{
    History::factory()->assistantMessage()->forConversation($conversation)->create([
        'content' => 'I prepared your glucose entry.',
        'tool_results' => [[
            'id' => 'fc_test',
            'name' => 'log_health_entry',
            'arguments' => ['log_type' => 'glucose'],
            'result' => json_encode([
                'status' => 'pending_approval',
                'approval_id' => $approval->id,
                'card' => [],
            ]),
            'result_id' => 'call_test',
        ]],
    ]);
}

it('hydrates a live pending approval card part from persisted tool results', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $approval = AgentApproval::factory()->forConversation($conversation)->create(['payload' => reloadGlucosePayload()]);

    seedApprovalMessage($conversation, $approval);

    $messages = resolve(BuildConversationMessagesAction::class)->handle(Conversation::query()->find($conversation->id));

    $approvalParts = array_values(array_filter(
        $messages[0]['parts'],
        fn (array $part): bool => $part['type'] === 'data-approval',
    ));

    expect($approvalParts)->toHaveCount(1)
        ->and($approvalParts[0]['data']['approvalId'])->toBe($approval->id)
        ->and($approvalParts[0]['data']['card']['status'])->toBe('pending')
        ->and($approvalParts[0]['data']['card']['summary'])->toBe('Glucose 140 mg/dL (fasting)')
        ->and($approvalParts[0]['data']['card']['can_approve'])->toBeTrue();
});

it('reflects the live executed status after reload, not the frozen streamed state', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $approval = AgentApproval::factory()->forConversation($conversation)->executed()->create(['payload' => reloadGlucosePayload()]);

    seedApprovalMessage($conversation, $approval);

    $messages = resolve(BuildConversationMessagesAction::class)->handle(Conversation::query()->find($conversation->id));

    $approvalPart = collect($messages[0]['parts'])->firstWhere('type', 'data-approval');

    expect($approvalPart['data']['card']['status'])->toBe('executed')
        ->and($approvalPart['data']['card']['can_approve'])->toBeFalse();
});
