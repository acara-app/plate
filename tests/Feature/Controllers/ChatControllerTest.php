<?php

declare(strict_types=1);

use App\Contracts\Ai\Advisor;
use App\Enums\AgentMode;
use App\Enums\AgentType;
use App\Enums\ModelName;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Laravel\Ai\Responses\StreamableAgentResponse;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    // Setup if needed
});

it('renders chat page with correct props when no conversation id provided', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('chat.create', ['agentType' => AgentType::Nutrition->value, 'mode' => AgentMode::Ask->value]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('conversationId', null)
            ->has('messages', 0)
            ->where('mode', AgentMode::Ask)
            ->where('agentType', AgentType::Nutrition->value)
        );
});

it('renders chat page with correct props with conversation id', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $history = History::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'Hello',
    ]);

    actingAs($user)
        ->get(route('chat.create', ['agentType' => AgentType::Nutrition->value, 'conversationId' => $conversation->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('conversationId', $conversation->id)
            ->has('messages', 1)
            ->where('messages.0.id', $history->id)
            ->where('messages.0.role', 'user')
            ->where('messages.0.parts.0.text', 'Hello')
        );
});

it('handles invalid conversation id gracefully', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('chat.create', ['agentType' => AgentType::Nutrition->value, 'conversationId' => 'invalid-id']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('conversationId', null)
            ->has('messages', 0)
        );
});

it('streams agent response correctly', function (): void {
    $user = User::factory()->create();

    $mockAgent = Mockery::mock(Advisor::class);
    $mockResponse = Mockery::mock(StreamableAgentResponse::class);

    app()->bind(Advisor::class, fn () => $mockAgent);

    $mockAgent->shouldReceive('withMode')
        ->once()
        ->with(AgentMode::Ask)
        ->andReturnSelf();

    $mockAgent->shouldReceive('forUser')
        ->once()
        ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
        ->andReturnSelf();

    $mockAgent->shouldReceive('stream')
        ->once()
        ->with('Hello API', [], null, ModelName::GPT_5_MINI->value)
        ->andReturn($mockResponse);

    $mockResponse->shouldReceive('usingVercelDataProtocol')
        ->once()
        ->andReturn($mockResponse);

    $mockResponse->shouldReceive('toResponse')
        ->once()
        ->andReturn(response('OK'));

    $url = route('chat.stream').'?mode='.AgentMode::Ask->value.'&model='.ModelName::GPT_5_MINI->value.'&agentType='.AgentType::Nutrition->value;

    actingAs($user)
        ->post($url, [
            'messages' => [
                ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello API']]],
            ],
        ])
        ->assertOk();
});

it('handles empty user message gracefully', function (): void {
    $user = User::factory()->create();

    $mockAgent = Mockery::mock(Advisor::class);
    $mockResponse = Mockery::mock(StreamableAgentResponse::class);

    app()->bind(Advisor::class, fn () => $mockAgent);

    $mockAgent->shouldReceive('withMode')->once()->andReturnSelf();
    $mockAgent->shouldReceive('forUser')->once()->andReturnSelf();

    $mockAgent->shouldReceive('stream')
        ->once()
        ->with('', [], null, ModelName::GPT_5_MINI->value)
        ->andReturn($mockResponse);

    $mockResponse->shouldReceive('usingVercelDataProtocol')->once()->andReturn($mockResponse);
    $mockResponse->shouldReceive('toResponse')->once()->andReturn(response('OK'));

    $url = route('chat.stream').'?mode='.AgentMode::Ask->value.'&model='.ModelName::GPT_5_MINI->value.'&agentType='.AgentType::Nutrition->value;

    actingAs($user)
        ->post($url, [
            'messages' => [
                ['role' => 'assistant', 'parts' => [['type' => 'text', 'text' => 'Hello']]],
            ],
        ])
        ->assertOk();
});

test('stream endpoint validation', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('chat.stream'), [])
        ->assertSessionHasErrors(['messages', 'mode', 'model', 'agentType']);
});
