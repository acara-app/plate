<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\ConversationSummary;

covers(ConversationSummary::class);

it('has correct fillable attributes', function (): void {
    $summary = new ConversationSummary();

    expect($summary->getGuarded())->toBe([]);
});

it('has correct casts', function (): void {
    $summary = new ConversationSummary();
    $casts = $summary->casts();

    expect($casts)
        ->toHaveKey('sequence_number', 'integer')
        ->toHaveKey('topics', 'array')
        ->toHaveKey('key_facts', 'array')
        ->toHaveKey('unresolved_threads', 'array')
        ->toHaveKey('resolved_threads', 'array')
        ->toHaveKey('message_count', 'integer');
});

it('uses conversation_summaries table', function (): void {
    $summary = new ConversationSummary();

    expect($summary->getTable())->toBe('conversation_summaries');
});

it('generates UUID id on creation', function (): void {
    $summary = ConversationSummary::factory()->create();

    expect($summary->id)->toBeString()
        ->and($summary->id)->not->toBeEmpty();
});

it('belongs to a conversation', function (): void {
    $conversation = Conversation::factory()->create();
    $summary = ConversationSummary::factory()->create(['conversation_id' => $conversation->id]);

    expect($summary->conversation)->toBeInstanceOf(Conversation::class)
        ->and($summary->conversation->id)->toBe($conversation->id);
});

it('can link to previous summary', function (): void {
    $conversation = Conversation::factory()->create();
    $firstSummary = ConversationSummary::factory()->create([
        'conversation_id' => $conversation->id,
        'sequence_number' => 1,
    ]);
    $secondSummary = ConversationSummary::factory()->create([
        'conversation_id' => $conversation->id,
        'sequence_number' => 2,
        'previous_summary_id' => $firstSummary->id,
    ]);

    expect($secondSummary->previousSummary)->toBeInstanceOf(ConversationSummary::class)
        ->and($secondSummary->previousSummary->id)->toBe($firstSummary->id);
});

it('previousSummary returns null when no previous summary', function (): void {
    $summary = ConversationSummary::factory()->create();

    expect($summary->previousSummary)->toBeNull();
});

it('has unresolved threads returns true when threads exist', function (): void {
    $summary = ConversationSummary::factory()->create([
        'unresolved_threads' => ['thread1', 'thread2'],
    ]);

    expect($summary->hasUnresolvedThreads())->toBeTrue();
});

it('has unresolved threads returns false when no threads', function (): void {
    $summary = ConversationSummary::factory()->create([
        'unresolved_threads' => [],
    ]);

    expect($summary->hasUnresolvedThreads())->toBeFalse();
});

it('forConversation scope filters correctly', function (): void {
    $conversation1 = Conversation::factory()->create();
    $conversation2 = Conversation::factory()->create();

    ConversationSummary::factory()->create(['conversation_id' => $conversation1->id, 'sequence_number' => 1]);
    ConversationSummary::factory()->create(['conversation_id' => $conversation1->id, 'sequence_number' => 2]);
    ConversationSummary::factory()->create(['conversation_id' => $conversation2->id, 'sequence_number' => 1]);

    $summaries = ConversationSummary::query()->forConversation($conversation1->id)->get();

    expect($summaries)->toHaveCount(2)
        ->and($summaries->every(fn ($s): bool => $s->conversation_id === $conversation1->id))->toBeTrue();
});

it('recent scope orders by sequence number descending with limit', function (): void {
    $conversation = Conversation::factory()->create();

    ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 1]);
    ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 2]);
    ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 3]);

    $recent = ConversationSummary::query()->forConversation($conversation->id)->recent(2)->get();

    expect($recent)->toHaveCount(2)
        ->and($recent->first()->sequence_number)->toBe(3)
        ->and($recent->last()->sequence_number)->toBe(2);
});

it('getNextSequenceNumber returns 1 when no summaries exist', function (): void {
    $conversation = Conversation::factory()->create();

    $nextNumber = ConversationSummary::getNextSequenceNumber($conversation->id);

    expect($nextNumber)->toBe(1);
});

it('getNextSequenceNumber increments from max existing', function (): void {
    $conversation = Conversation::factory()->create();

    ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 1]);
    ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 3]);

    $nextNumber = ConversationSummary::getNextSequenceNumber($conversation->id);

    expect($nextNumber)->toBe(4);
});

it('getLatestForConversation returns most recent summary', function (): void {
    $conversation = Conversation::factory()->create();

    ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 1]);
    $latest = ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 2]);

    $found = ConversationSummary::getLatestForConversation($conversation->id);

    expect($found->id)->toBe($latest->id)
        ->and($found->sequence_number)->toBe(2);
});

it('getLatestForConversation returns null when no summaries', function (): void {
    $conversation = Conversation::factory()->create();

    $found = ConversationSummary::getLatestForConversation($conversation->id);

    expect($found)->toBeNull();
});

it('getRecentForContext returns recent summaries in reverse order', function (): void {
    $conversation = Conversation::factory()->create();

    ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 1]);
    ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 2]);
    ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 3]);

    $recent = ConversationSummary::getRecentForContext($conversation->id, 3);

    expect($recent)->toHaveCount(3)
        ->and($recent->first()->sequence_number)->toBe(1)
        ->and($recent->last()->sequence_number)->toBe(3);
});

it('getRecentForContext respects count parameter', function (): void {
    $conversation = Conversation::factory()->create();

    ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 1]);
    ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 2]);
    ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 3]);

    $recent = ConversationSummary::getRecentForContext($conversation->id, 2);

    expect($recent)->toHaveCount(2);
});

it('getRecentForContext returns empty collection for non-existent conversation', function (): void {
    $recent = ConversationSummary::getRecentForContext('non-existent-id');

    expect($recent)->toBeEmpty();
});

it('factory creates valid summary', function (): void {
    $summary = ConversationSummary::factory()->create();

    expect($summary->exists)->toBeTrue()
        ->and($summary->summary)->toBeString()
        ->and($summary->conversation_id)->toBeString()
        ->and($summary->sequence_number)->toBeInt();
});
