<?php

declare(strict_types=1);

use App\Actions\AbandonPendingApprovals;
use App\Actions\RecordApprovalDecisions;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('still shows approval cards that were left unanswered after a reload', function (string $leftAs, string $notice): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    $approvals = ['call_abc' => 'Eggs', 'call_def' => 'Coffee'];

    History::factory()
        ->forConversation($conversation)
        ->awaitingApproval($approvals, arguments: ['log_type' => 'food', 'summary' => 'Eggs and coffee'])
        ->create([
            'content' => 'Let me log that.',
            'meta' => History::streamMeta('stream-1', History::STREAM_STATUS_COMPLETED, ['approvals' => $approvals]),
        ]);

    match ($leftAs) {
        'abandoned' => resolve(AbandonPendingApprovals::class)->handle($conversation->id),
        'submitted' => resolve(RecordApprovalDecisions::class)->handle($conversation->fresh(), ['call_abc' => ['action' => 'approve']]),
    };

    $this->actingAs($user);

    visit(route('chat.create', $conversation->id, absolute: false))
        ->assertSee($notice);
})->with([
    'the conversation moved on' => ['abandoned', 'Not saved. The conversation moved on before you chose.'],
    'one of two choices recorded' => ['submitted', 'Choice recorded. Answer the other requests to continue.'],
]);
