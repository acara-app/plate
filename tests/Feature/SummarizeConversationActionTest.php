<?php

declare(strict_types=1);

use App\Actions\SummarizeConversationAction;
use App\Contracts\SummarizesConversation;
use App\Models\Conversation;
use App\Models\ConversationSummary;
use App\Models\History;
use App\Models\User;

covers(SummarizeConversationAction::class);

function createConversationWithMessages(User $user, int $count): Conversation
{
    $conversation = Conversation::factory()->forUser($user)->create();

    History::factory()
        ->count($count)
        ->forConversation($conversation)
        ->sequence(
            fn ($sequence): array => [
                'role' => $sequence->index % 2 === 0 ? 'user' : 'assistant',
                'created_at' => now()->subMinutes($count - $sequence->index),
            ],
        )
        ->create();

    return $conversation;
}

describe('shouldSummarize', function (): void {
    it('returns false when total messages are below buffer', function (): void {
        $user = User::factory()->create();
        $conversation = createConversationWithMessages($user, 20);

        $action = resolve(SummarizeConversationAction::class);

        expect($action->shouldSummarize($conversation))->toBeFalse();
    });

    it('returns false when unsummarized old messages are below threshold', function (): void {
        $user = User::factory()->create();
        $conversation = createConversationWithMessages($user, 30);

        $action = resolve(SummarizeConversationAction::class);

        expect($action->shouldSummarize($conversation))->toBeFalse();
    });

    it('returns true when enough unsummarized old messages exist', function (): void {
        $user = User::factory()->create();
        $conversation = createConversationWithMessages($user, 50);

        $action = resolve(SummarizeConversationAction::class);

        expect($action->shouldSummarize($conversation))->toBeTrue();
    });

    it('returns false when old messages are already summarized', function (): void {
        $user = User::factory()->create();
        $conversation = createConversationWithMessages($user, 50);

        $summary = ConversationSummary::query()->create([
            'conversation_id' => $conversation->id,
            'sequence_number' => 1,
            'summary' => 'Test summary',
            'topics' => ['test'],
            'key_facts' => [],
            'unresolved_threads' => [],
            'resolved_threads' => [],
            'start_message_id' => $conversation->messages->first()->id,
            'end_message_id' => $conversation->messages->skip(19)->first()->id,
            'message_count' => 20,
        ]);

        History::query()->where('conversation_id', $conversation->id)->oldest()
            ->take(25)
            ->get()
            ->each(fn (History $h) => $h->update(['summary_id' => $summary->id]));

        $action = resolve(SummarizeConversationAction::class);

        expect($action->shouldSummarize($conversation))->toBeFalse();
    });
});

describe('handle', function (): void {
    it('creates a summary and marks messages', function (): void {
        $user = User::factory()->create();
        $conversation = createConversationWithMessages($user, 50);

        $mockAgent = Mockery::mock(SummarizesConversation::class);
        $mockAgent->shouldReceive('summarize')
            ->once()
            ->andReturn([
                'summary' => 'The user discussed nutrition and meal planning.',
                'topics' => ['nutrition', 'meal planning'],
                'key_facts' => ['User prefers vegetarian meals'],
                'unresolved_threads' => ['Weekly grocery list'],
                'resolved_threads' => ['Daily calorie target set'],
            ]);

        $action = new SummarizeConversationAction($mockAgent);
        $summary = $action->handle($conversation);

        expect($summary)
            ->toBeInstanceOf(ConversationSummary::class)
            ->summary->toBe('The user discussed nutrition and meal planning.')
            ->topics->toBe(['nutrition', 'meal planning'])
            ->key_facts->toBe(['User prefers vegetarian meals'])
            ->unresolved_threads->toBe(['Weekly grocery list'])
            ->resolved_threads->toBe(['Daily calorie target set'])
            ->sequence_number->toBe(1)
            ->conversation_id->toBe($conversation->id)
            ->message_count->toBeGreaterThan(0);

        $summarizedCount = History::query()->where('conversation_id', $conversation->id)
            ->whereNotNull('summary_id')
            ->count();

        expect($summarizedCount)->toBe($summary->message_count);
    });

    it('returns null when summarization is not needed', function (): void {
        $user = User::factory()->create();
        $conversation = createConversationWithMessages($user, 10);

        $action = resolve(SummarizeConversationAction::class);

        expect($action->handle($conversation))->toBeNull();
    });

    it('links to previous summary for continuity', function (): void {
        $user = User::factory()->create();
        $conversation = createConversationWithMessages($user, 70);

        $firstSummary = ConversationSummary::query()->create([
            'conversation_id' => $conversation->id,
            'sequence_number' => 1,
            'summary' => 'First summary',
            'topics' => ['fitness'],
            'key_facts' => [],
            'unresolved_threads' => ['workout plan'],
            'resolved_threads' => [],
            'start_message_id' => $conversation->messages->first()->id,
            'end_message_id' => $conversation->messages->skip(19)->first()->id,
            'message_count' => 20,
        ]);

        History::query()->where('conversation_id', $conversation->id)->oldest()
            ->take(20)
            ->get()
            ->each(fn (History $h) => $h->update(['summary_id' => $firstSummary->id]));

        $mockAgent = Mockery::mock(SummarizesConversation::class);
        $mockAgent->shouldReceive('summarize')
            ->once()
            ->andReturn([
                'summary' => 'Second summary continuing discussion.',
                'topics' => ['fitness', 'diet'],
                'key_facts' => [],
                'unresolved_threads' => [],
                'resolved_threads' => ['workout plan'],
            ]);

        $action = new SummarizeConversationAction($mockAgent);
        $summary = $action->handle($conversation);

        expect($summary)
            ->toBeInstanceOf(ConversationSummary::class)
            ->sequence_number->toBe(2)
            ->previous_summary_id->toBe($firstSummary->id);
    });

    it('returns null when messages are empty', function (): void {
        $user = User::factory()->create();
        $conversation = createConversationWithMessages($user, 50);

        History::query()->where('conversation_id', $conversation->id)
            ->update(['summary_id' => 'fake-summary-id']);

        $mockAgent = Mockery::mock(SummarizesConversation::class);
        $mockAgent->shouldNotReceive('summarize');

        $action = new SummarizeConversationAction($mockAgent);
        $result = $action->handle($conversation);

        expect($result)->toBeNull();
    });

    it('returns null when summarization fails', function (): void {
        $user = User::factory()->create();
        $conversation = createConversationWithMessages($user, 50);

        $mockAgent = Mockery::mock(SummarizesConversation::class);
        $mockAgent->shouldReceive('summarize')
            ->once()
            ->andThrow(new Exception('API failure'));

        $action = new SummarizeConversationAction($mockAgent);
        $result = $action->handle($conversation);

        expect($result)->toBeNull();
    });

    it('returns null when agent returns incomplete structured data', function (): void {
        $user = User::factory()->create();
        $conversation = createConversationWithMessages($user, 50);

        $mockAgent = Mockery::mock(SummarizesConversation::class);
        $mockAgent->shouldReceive('summarize')
            ->once()
            ->andReturn([
                'topics' => ['nutrition'],
            ]);

        $action = new SummarizeConversationAction($mockAgent);
        $result = $action->handle($conversation);

        expect($result)->toBeNull();
    });
});
