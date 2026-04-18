<?php

declare(strict_types=1);

use App\Actions\SummarizeConversationAction;
use App\Jobs\SummarizeConversationJob;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

covers(SummarizeConversationJob::class);

it('implements ShouldBeUnique and ShouldQueue', function (): void {
    $conversation = Conversation::factory()->create();
    $job = new SummarizeConversationJob($conversation);

    expect($job)->toBeInstanceOf(ShouldBeUnique::class)
        ->and($job)->toBeInstanceOf(ShouldQueue::class);
});

it('has conversation as public property', function (): void {
    $conversation = Conversation::factory()->create();
    $job = new SummarizeConversationJob($conversation);

    expect($job->conversation)->toBe($conversation);
});

it('returns WithoutOverlapping middleware', function (): void {
    $conversation = Conversation::factory()->create();
    $job = new SummarizeConversationJob($conversation);

    $middleware = $job->middleware();

    expect($middleware)->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(WithoutOverlapping::class);
});

it('uniqueId returns conversation id', function (): void {
    $conversation = Conversation::factory()->create();
    $job = new SummarizeConversationJob($conversation);

    expect($job->uniqueId())->toBe($conversation->id);
});

it('backoff returns correct delays', function (): void {
    $conversation = Conversation::factory()->create();
    $job = new SummarizeConversationJob($conversation);

    expect($job->backoff())->toBe([30, 60, 120]);
});

it('clears dispatch timestamp when the job fails', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create([
        'summarization_dispatched_at' => now(),
    ]);

    $job = new SummarizeConversationJob($conversation);
    $job->failed(new RuntimeException('test'));

    $conversation->refresh();
    expect($conversation->summarization_dispatched_at)->toBeNull();
});

it('clears dispatch timestamp after handling', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create([
        'summarization_dispatched_at' => now(),
    ]);

    History::factory()
        ->count(50)
        ->forConversation($conversation)
        ->sequence(
            fn ($sequence): array => [
                'role' => $sequence->index % 2 === 0 ? 'user' : 'assistant',
                'created_at' => now()->subMinutes(50 - $sequence->index),
            ],
        )
        ->create();

    $job = new SummarizeConversationJob($conversation);
    $job->handle(resolve(SummarizeConversationAction::class));

    $conversation->refresh();
    expect($conversation->summarization_dispatched_at)->toBeNull();
});
