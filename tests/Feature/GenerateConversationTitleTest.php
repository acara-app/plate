<?php

declare(strict_types=1);

use App\Actions\GenerateConversationTitleAction;
use App\Contracts\GeneratesConversationTitle;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

covers(GenerateConversationTitleAction::class);

function runTitleAction(Conversation $conversation, GeneratesConversationTitle $agent): void
{
    new GenerateConversationTitleAction($agent)->handle($conversation);
}

it('generates and saves a title from the first user message', function (?string $override, string $model, ?string $effort): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create(['title' => Conversation::DEFAULT_TITLE]);

    History::factory()->forConversation($conversation)->create([
        'role' => 'user',
        'content' => 'How do I lower my morning glucose spikes?',
    ]);

    config()->set([
        'ai.providers.openai.key' => 'test-key',
        'ai.providers.openai.url' => 'https://openai.test/v1',
    ]);
    if ($override !== null) {
        config()->set('ai.providers.openai.models.text.cheapest', $override);
    }

    Http::fake([
        'https://openai.test/v1/responses' => Http::response([
            'id' => 'resp_title',
            'model' => $model,
            'status' => 'completed',
            'output' => [[
                'type' => 'message',
                'role' => 'assistant',
                'content' => [['type' => 'output_text', 'text' => json_encode(['title' => 'Lowering morning glucose spikes'], JSON_THROW_ON_ERROR)]],
            ]],
        ]),
    ]);

    resolve(GenerateConversationTitleAction::class)->handle($conversation);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://openai.test/v1/responses'
        && $request['model'] === $model
        && data_get($request->data(), 'reasoning.effort') === $effort
        && data_get($request->data(), 'text.format.type') === 'json_schema');

    expect($conversation->refresh()->title)->toBe('Lowering morning glucose spikes');
})->with([
    'Luna default' => [null, 'gpt-6-luna', 'none'],
    'model override' => ['gpt-5-nano', 'gpt-5-nano', null],
]);

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
