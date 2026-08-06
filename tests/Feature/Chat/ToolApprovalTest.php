<?php

declare(strict_types=1);

use App\Actions\AbandonPendingApprovals;
use App\Actions\BuildConversationMessagesAction;
use App\Actions\CompletePendingChatStreamTurn;
use App\Actions\CreatePendingChatStreamTurn;
use App\Actions\RecordApprovalDecisions;
use App\Actions\ResumeChatStream;
use App\Ai\AgentRequest;
use App\Ai\Agents\AgentRunner;
use App\Data\ChatStreamResult;
use App\Jobs\ProcessChatStream;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use App\Services\Ai\PlateConversationStore;
use App\Services\StreamAggregator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Exceptions\ApprovalMismatchException;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Streaming\Events\ToolApprovalRequest;

function pausedConversation(User $user, string $reason = 'Glucose 140 mg/dL, fasting'): Conversation
{
    $conversation = Conversation::factory()->forUser($user)->create();

    History::factory()
        ->forConversation($conversation)
        ->awaitingApproval(['call_abc' => $reason])
        ->create([
            'content' => 'Let me log that.',
            'tool_calls' => [['id' => 'call_abc', 'name' => 'log_health_entry', 'arguments' => ['log_type' => 'glucose', 'summary' => $reason]]],
            'meta' => History::streamMeta('stream-1', History::STREAM_STATUS_COMPLETED, [
                'model' => 'gemini-3.5-flash',
                'approvals' => ['call_abc' => $reason],
            ]) + ['provider' => 'gemini', 'provider_content_blocks' => [['type' => 'function_call']]],
        ]);

    return $conversation->fresh();
}

it('records the paused tool call on the assistant turn instead of executing it', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    $assistant = History::factory()->forConversation($conversation)->create([
        'role' => MessageRole::Assistant,
        'meta' => History::streamMeta('stream-1', History::STREAM_STATUS_PENDING),
    ]);

    resolve(CompletePendingChatStreamTurn::class)->handle(
        conversationId: $conversation->id,
        user: $user,
        userMessageId: null,
        assistantMessageId: $assistant->id,
        result: new ChatStreamResult(
            text: 'Confirm below.',
            toolCalls: [['id' => 'call_abc', 'name' => 'log_health_entry', 'arguments' => []]],
            pendingApprovals: ['call_abc' => 'Glucose 140 mg/dL, fasting'],
        ),
        status: History::STREAM_STATUS_COMPLETED,
        providerContentBlocks: [['type' => 'function_call']],
        provider: 'gemini',
    );

    $assistant = $assistant->fresh();

    expect($assistant->pendingApprovals())->toBe(['call_abc' => 'Glucose 140 mg/dL, fasting'])
        ->and($assistant->requestedApprovals())->toBe(['call_abc' => 'Glucose 140 mg/dL, fasting'])
        ->and($assistant->providerContentBlocks())->toBe([['type' => 'function_call']])
        ->and($assistant->provider())->toBe('gemini');
});

it('surfaces pending approvals from the stream so the browser can render a card', function (): void {
    $payload = resolve(StreamAggregator::class)->normalizeEvent(new ToolApprovalRequest(
        id: 'evt-1',
        pendingApprovals: new Collection([new PendingApproval('call_abc', 'log_health_entry', ['log_type' => 'glucose'], 'Glucose 140 mg/dL')]),
        timestamp: 1,
        providerContentBlocks: [['type' => 'function_call']],
    ));

    expect($payload['type'])->toBe('tool_approval_request')
        ->and($payload['approvals'][0]['id'])->toBe('call_abc')
        ->and($payload['approvals'][0]['reason'])->toBe('Glucose 140 mg/dL')
        ->and($payload)->not->toHaveKey('provider_content_blocks');
});

it('queues a resumed stream carrying the approval decision', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversation = pausedConversation($user);

    $turn = $this->actingAs($user)
        ->postJson(route('approvals.decide', $conversation->id), [
            'decisions' => ['call_abc' => ['action' => 'approve']],
        ])
        ->assertAccepted()
        ->json();

    expect($turn['userMessageId'])->toBeNull();

    Queue::assertPushed(ProcessChatStream::class, fn (ProcessChatStream $job): bool => $job->conversationId === $conversation->id
        && $job->userMessageId === null
        && $job->modelName === 'gemini-3.5-flash'
        && $job->decisions === [['id' => 'call_abc', 'action' => 'approve', 'result' => null]]);
});

it('rejects a decision for a tool call the conversation is not waiting on', function (): void {
    $user = User::factory()->create();
    $conversation = pausedConversation($user);

    expect(fn () => resolve(ResumeChatStream::class)->handle($conversation, $user, ['call_stale' => ['action' => 'approve']]))
        ->toThrow(ApprovalMismatchException::class);
});

it('rejects a decision when nothing is awaiting approval', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    expect(fn () => resolve(ResumeChatStream::class)->handle($conversation, $user, ['call_abc' => ['action' => 'approve']]))
        ->toThrow(ApprovalMismatchException::class);
});

it('does not let another user decide on a conversation they do not own', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $conversation = pausedConversation($owner);

    $this->actingAs($intruder)
        ->postJson(route('approvals.decide', $conversation->id), [
            'decisions' => ['call_abc' => ['action' => 'approve']],
        ])
        ->assertForbidden();
});

it('replays a paused turn with its provider state so the call can be resumed', function (): void {
    $user = User::factory()->create();
    $conversation = pausedConversation($user);

    $replay = new ReflectionMethod(AgentRunner::class, 'toAiMessages');
    $messages = collect($replay->invoke(resolve(AgentRunner::class), $conversation->messages()->first()));

    $assistant = $messages->first(fn (object $message): bool => $message instanceof AssistantMessage);

    expect($messages)->toHaveCount(1)
        ->and($assistant->toolCalls->first()->id)->toBe('call_abc')
        ->and($assistant->providerContentBlocks)->toBe([['type' => 'function_call']])
        ->and($assistant->providerContentBlocksProvider)->toBe('gemini');
});

it('rebuilds the approval card on reload and marks it resolved once decided', function (): void {
    $user = User::factory()->create();
    $conversation = pausedConversation($user);

    $parts = collect(resolve(BuildConversationMessagesAction::class)->handle($conversation))
        ->flatMap(fn (array $message): array => $message['parts'])
        ->firstWhere('type', 'data-approval');

    expect($parts['data']['toolCallId'])->toBe('call_abc')
        ->and($parts['data']['tool'])->toBe('log_health_entry')
        ->and($parts['data']['reason'])->toBe('Glucose 140 mg/dL, fasting')
        ->and($parts['data']['status'])->toBe('pending');

    $paused = $conversation->messages()->first();
    $paused->forceFill([
        'approval_state' => ['pending' => []],
        'tool_results' => [['id' => 'call_abc', 'name' => 'log_health_entry', 'result' => 'Saved.']],
    ])->save();

    $resolved = collect(resolve(BuildConversationMessagesAction::class)->handle($conversation->fresh()))
        ->flatMap(fn (array $message): array => $message['parts'])
        ->firstWhere('type', 'data-approval');

    expect($resolved['data']['status'])->toBe('approved');
});

it('marks persistence app-managed for streamed turns and hands it back for sync turns', function (): void {
    $user = User::factory()->create();
    $request = new AgentRequest(message: 'Hello', conversationId: 'conv-1');

    $prepare = new ReflectionMethod(AgentRunner::class, 'prepare');
    $runner = resolve(AgentRunner::class);

    $prepare->invoke($runner, $request, $user, true);

    expect(PlateConversationStore::appManaged())->toBeTrue();

    $prepare->invoke($runner, $request, $user, false);

    expect(PlateConversationStore::appManaged())->toBeFalse();
});

it('subscribes the browser to the pause event the server broadcasts', function (): void {
    $payload = resolve(StreamAggregator::class)->normalizeEvent(new ToolApprovalRequest(
        id: 'evt-1',
        pendingApprovals: new Collection([new PendingApproval('call_abc', 'log_health_entry', [], null)]),
        timestamp: 1,
    ));

    $subscribed = File::get(resource_path('js/hooks/chat/use-stream-channel.ts'));

    expect($subscribed)->toContain("'.".$payload['type']."'");
});

it('drops a pause from a turn that never finished, since it cannot be resumed', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    $assistant = History::factory()->forConversation($conversation)->create([
        'role' => MessageRole::Assistant,
        'meta' => History::streamMeta('stream-1', History::STREAM_STATUS_PENDING),
    ]);

    resolve(CompletePendingChatStreamTurn::class)->handle(
        conversationId: $conversation->id,
        user: $user,
        userMessageId: null,
        assistantMessageId: $assistant->id,
        result: new ChatStreamResult(
            text: 'Confirm below.',
            toolCalls: [['id' => 'call_abc', 'name' => 'log_health_entry', 'arguments' => []]],
            pendingApprovals: ['call_abc' => 'Glucose 140 mg/dL, fasting'],
        ),
        status: History::STREAM_STATUS_FAILED,
    );

    $assistant = $assistant->fresh();

    expect($assistant->approval_state)->toBeNull()
        ->and($assistant->requestedApprovals())->toBe([])
        ->and($assistant->hasPendingApprovals())->toBeFalse();
});

it('abandons a pending approval once the conversation moves on', function (): void {
    $user = User::factory()->create();
    $conversation = pausedConversation($user);

    resolve(CreatePendingChatStreamTurn::class)->handle(
        $conversation,
        $user,
        'never mind, what should I eat instead?',
        [],
        'web',
    );

    expect($conversation->fresh()->pausedApprovalTurn())->toBeNull();

    $card = collect(resolve(BuildConversationMessagesAction::class)->handle($conversation->fresh()))
        ->flatMap(fn (array $message): array => $message['parts'])
        ->firstWhere('type', 'data-approval');

    expect($card['data']['status'])->toBe('abandoned');
});

it('refuses a decision on an approval the conversation has moved past', function (): void {
    $user = User::factory()->create();
    $conversation = pausedConversation($user);

    resolve(AbandonPendingApprovals::class)->handle($conversation->id);

    $this->actingAs($user)
        ->postJson(route('approvals.decide', $conversation->id), [
            'decisions' => ['call_abc' => ['action' => 'approve']],
        ])
        ->assertStatus(409);
});

it('waits for every pending call before resuming, rather than dismissing the rest', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    History::factory()
        ->forConversation($conversation)
        ->awaitingApproval(['call_abc' => 'Eggs', 'call_def' => 'Coffee'])
        ->create([
            'tool_calls' => [
                ['id' => 'call_abc', 'name' => 'log_health_entry', 'arguments' => []],
                ['id' => 'call_def', 'name' => 'log_health_entry', 'arguments' => []],
            ],
            'meta' => History::streamMeta('stream-1', History::STREAM_STATUS_COMPLETED, [
                'approvals' => ['call_abc' => 'Eggs', 'call_def' => 'Coffee'],
            ]),
        ]);

    $this->actingAs($user)
        ->postJson(route('approvals.decide', $conversation->id), [
            'decisions' => ['call_abc' => ['action' => 'approve']],
        ])
        ->assertAccepted()
        ->assertJson(['status' => 'recorded', 'awaiting' => ['call_def']]);

    Queue::assertNotPushed(ProcessChatStream::class);

    $paused = $conversation->fresh()->pausedApprovalTurn();
    expect(array_keys($paused->pendingApprovals()))->toBe(['call_abc', 'call_def'])
        ->and(array_keys($paused->recordedApprovalDecisions()))->toBe(['call_abc']);

    $this->actingAs($user)
        ->postJson(route('approvals.decide', $conversation->id), [
            'decisions' => ['call_def' => ['action' => 'reject']],
        ])
        ->assertAccepted();

    Queue::assertPushed(ProcessChatStream::class, fn (ProcessChatStream $job): bool => collect($job->decisions)->pluck('action', 'id')->all() === ['call_abc' => 'approve', 'call_def' => 'reject']);
});

it('shows a card as submitted while it waits on a sibling decision', function (): void {
    $user = User::factory()->create();
    $conversation = pausedConversation($user);

    resolve(RecordApprovalDecisions::class)->handle($conversation, ['call_abc' => ['action' => 'approve']]);

    $card = collect(resolve(BuildConversationMessagesAction::class)->handle($conversation->fresh()))
        ->flatMap(fn (array $message): array => $message['parts'])
        ->firstWhere('type', 'data-approval');

    expect($card['data']['status'])->toBe('submitted');
});
