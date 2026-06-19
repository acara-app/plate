<?php

declare(strict_types=1);

use App\Console\Commands\PurgeExpiredConversationsCommand;
use App\Models\Conversation;
use App\Models\History;

covers(PurgeExpiredConversationsCommand::class);

it('deletes expired unpinned conversations and their messages', function (): void {
    $conversation = Conversation::factory()->stale()->create();
    History::factory()->count(3)->forConversation($conversation)->create();

    $this->artisan('conversations:purge-expired')->assertSuccessful();

    $this->assertModelMissing($conversation);
    $this->assertDatabaseMissing('agent_conversation_messages', [
        'conversation_id' => $conversation->id,
    ]);
});

it('preserves pinned conversations even when stale', function (): void {
    $pinned = Conversation::factory()->pinned()->stale(10)->create();

    $this->artisan('conversations:purge-expired')->assertSuccessful();

    $this->assertModelExists($pinned);
});

it('preserves conversations within the retention window', function (): void {
    $recent = Conversation::factory()->create();

    $this->artisan('conversations:purge-expired')->assertSuccessful();

    $this->assertModelExists($recent);
});

it('skips a stale conversation that still has a live pending stream', function (): void {
    $conversation = Conversation::factory()->stale()->create();
    History::factory()->forConversation($conversation)->pendingStream()->create();

    $this->artisan('conversations:purge-expired')->assertSuccessful();

    $this->assertModelExists($conversation);
});

it('respects the per-run limit', function (): void {
    Conversation::factory()->count(3)->stale()->create();

    $this->artisan('conversations:purge-expired', ['--limit' => 2])->assertSuccessful();

    expect(Conversation::query()->count())->toBe(1);
});
