<?php

declare(strict_types=1);

use App\Actions\GenerateConversationTitleAction;
use App\Contracts\GeneratesConversationTitle;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;

covers(GenerateConversationTitleAction::class);

function runTitleAction(Conversation $conversation, GeneratesConversationTitle $agent): void
{
    new GenerateConversationTitleAction($agent)->handle($conversation);
}

it('generates and saves a title from the first user message', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create(['title' => Conversation::DEFAULT_TITLE]);

    History::factory()->forConversation($conversation)->create([
        'role' => 'user',
        'content' => 'How do I lower my morning glucose spikes?',
    ]);

    $agent = Mockery::mock(GeneratesConversationTitle::class);
    $agent->shouldReceive('generate')
        ->once()
        ->andReturn('Lowering morning glucose spikes');

    runTitleAction($conversation, $agent);

    expect($conversation->refresh()->title)->toBe('Lowering morning glucose spikes');
});

it('does nothing when the conversation already has a real title', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create(['title' => 'Existing title']);

    History::factory()->forConversation($conversation)->create([
        'role' => 'user',
        'content' => 'Hi there',
    ]);

    $agent = Mockery::mock(GeneratesConversationTitle::class);
    $agent->shouldNotReceive('generate');

    runTitleAction($conversation, $agent);

    expect($conversation->refresh()->title)->toBe('Existing title');
});

it('keeps the default title when there is no user message yet', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create(['title' => Conversation::DEFAULT_TITLE]);

    History::factory()->forConversation($conversation)->create([
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    $agent = Mockery::mock(GeneratesConversationTitle::class);
    $agent->shouldNotReceive('generate');

    runTitleAction($conversation, $agent);

    expect($conversation->refresh()->title)->toBe(Conversation::DEFAULT_TITLE);
});

it('keeps the default title when generation throws', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create(['title' => Conversation::DEFAULT_TITLE]);

    History::factory()->forConversation($conversation)->create([
        'role' => 'user',
        'content' => 'Hi there',
    ]);

    $agent = Mockery::mock(GeneratesConversationTitle::class);
    $agent->shouldReceive('generate')->once()->andThrow(new Exception('API failure'));

    runTitleAction($conversation, $agent);

    expect($conversation->refresh()->title)->toBe(Conversation::DEFAULT_TITLE);
});

it('keeps the default title when generation returns blank', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create(['title' => Conversation::DEFAULT_TITLE]);

    History::factory()->forConversation($conversation)->create([
        'role' => 'user',
        'content' => 'Hi there',
    ]);

    $agent = Mockery::mock(GeneratesConversationTitle::class);
    $agent->shouldReceive('generate')->once()->andReturn('   ');

    runTitleAction($conversation, $agent);

    expect($conversation->refresh()->title)->toBe(Conversation::DEFAULT_TITLE);
});
