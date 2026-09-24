<?php

declare(strict_types=1);

use App\Ai\Agents\ConversationTitleGeneratorAgent;
use App\Listeners\TrackAiUsage;
use App\Models\AiUsage;
use App\Models\User;
use Laravel\Ai\AiManager;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;

covers(TrackAiUsage::class);

it('records cached and reasoning tokens apart from the base token counts so they are not billed twice', function (): void {
    $this->actingAs(User::factory()->create());

    $prompt = new AgentPrompt(
        resolve(ConversationTitleGeneratorAgent::class),
        'Hi',
        [],
        resolve(AiManager::class)->textProvider('openai'),
        'gpt-5-mini',
    );

    $response = new AgentResponse('invocation-1', 'Hello', new TextUsage(
        inputTokens: 1000,
        outputTokens: 500,
        cacheReadInputTokens: 200,
        cacheWriteInputTokens: 50,
        reasoningTokens: 100,
    ), new Meta('openai', 'gpt-5-mini'));

    event(new AgentPrompted('invocation-1', $prompt, $response));

    expect(AiUsage::query()->sole())
        ->prompt_tokens->toBe(750)
        ->completion_tokens->toBe(400)
        ->cache_read_input_tokens->toBe(200)
        ->cache_write_input_tokens->toBe(50)
        ->reasoning_tokens->toBe(100)
        ->totalTokens()->toBe(1500);
});
