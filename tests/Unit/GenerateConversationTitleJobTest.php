<?php

declare(strict_types=1);

use App\Actions\GenerateConversationTitleAction;
use App\Contracts\GeneratesConversationTitle;
use App\Jobs\GenerateConversationTitleJob;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;

covers(GenerateConversationTitleJob::class);

it('implements ShouldBeUnique and ShouldQueue', function (): void {
    $job = new GenerateConversationTitleJob(Conversation::factory()->create());

    expect($job)->toBeInstanceOf(ShouldBeUnique::class)
        ->and($job)->toBeInstanceOf(ShouldQueue::class);
});

it('uniqueId returns the conversation id', function (): void {
    $conversation = Conversation::factory()->create();
    $job = new GenerateConversationTitleJob($conversation);

    expect($job->uniqueId())->toBe($conversation->id);
});

it('backoff returns exponential delays', function (): void {
    $job = new GenerateConversationTitleJob(Conversation::factory()->create());

    expect($job->backoff())->toBe([30, 60, 120]);
});

it('updates the conversation title when handled', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create(['title' => Conversation::DEFAULT_TITLE]);

    History::factory()->forConversation($conversation)->create([
        'role' => 'user',
        'content' => 'Plan my week of meals',
    ]);

    $this->mock(GeneratesConversationTitle::class)
        ->shouldReceive('generate')->once()->andReturn('Weekly meal planning');

    $job = new GenerateConversationTitleJob($conversation);
    $job->handle(resolve(GenerateConversationTitleAction::class));

    expect($conversation->refresh()->title)->toBe('Weekly meal planning');
});
