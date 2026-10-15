<?php

declare(strict_types=1);

use App\Ai\Agents\ConversationTitleGeneratorAgent;

it('promotes a configured model without changing the agent or an enum', function (): void {
    config()->set('ai.model_catalog.test-model', [
        'provider' => 'openai', 'max_output_tokens' => 12000,
        'capabilities' => ['structured_output'], 'options' => [],
        'pricing' => ['input' => 1.0, 'output' => 3.0, 'reasoning' => 3.0, 'cache_read' => 0.1],
    ]);
    config()->set('ai.agent_profiles.'.ConversationTitleGeneratorAgent::class.'.standard', 'test-model');
    ConversationTitleGeneratorAgent::fake([['title' => 'A new conversation']]);

    resolve(ConversationTitleGeneratorAgent::class)->generate('Hello', 'English', 'en');

    ConversationTitleGeneratorAgent::assertPrompted(fn ($prompt): bool => $prompt->model === 'test-model'
        && $prompt->provider->name() === 'openai');
});

it('uses the catalog for every concrete agent including SDK delegated calls', function (string $agentClass): void {
    config()->set('ai.model_catalog.test-model', [
        'provider' => 'openai', 'max_output_tokens' => 12000,
        'capabilities' => ['structured_output', 'tools', 'vision'], 'options' => [],
        'pricing' => ['input' => 1.0, 'output' => 3.0, 'reasoning' => 3.0, 'cache_read' => 0.1],
    ]);
    config()->set('ai.agent_profiles.'.$agentClass.'.standard', 'test-model');
    $agentClass::fake(['A response']);

    resolve($agentClass)->prompt('A task');

    $agentClass::assertPrompted(fn ($prompt): bool => $prompt->model === 'test-model'
        && $prompt->provider->name() === 'openai');
})->with([
    App\Ai\Agents\AgentRunner::class,
    App\Ai\Agents\FitnessSpecialist::class,
    App\Ai\Agents\HealthSpecialist::class,
    App\Ai\Agents\NutritionSpecialist::class,
    App\Ai\Agents\MealPlanSpecialist::class,
    App\Ai\Agents\GlucoseSpikeSpecialist::class,
    App\Ai\Agents\CaffeineGuidanceAgent::class,
    App\Ai\Agents\GroceryListAgent::class,
    App\Ai\Agents\MealPlanAgent::class,
    App\Ai\Agents\SingleMealAgent::class,
    App\Ai\Agents\SpikePredictorAgent::class,
    App\Ai\Agents\FoodPhotoAnalyzerAgent::class,
    ConversationTitleGeneratorAgent::class,
    App\Ai\Agents\ConversationSummarizerAgent::class,
    App\Ai\Agents\EnrichAttributeMetadataAgent::class,
]);

it('freezes the resolved models when a chat turn is queued', function (): void {
    Illuminate\Support\Facades\Queue::fake();
    $this->mock(App\Services\StreamEventStore::class)->shouldReceive('clear')->once();
    config()->set('ai.agent_profiles.'.App\Ai\Agents\AgentRunner::class.'.standard', 'gpt-6.1-sol');
    $user = App\Models\User::factory()->create();
    $conversation = App\Models\Conversation::factory()->create(App\Models\Conversation::participantAttributes($user));

    $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello']]]],
    ])->assertAccepted();
    config()->set('ai.agent_profiles.'.App\Ai\Agents\AgentRunner::class.'.standard', 'gpt-5.4-mini');

    Illuminate\Support\Facades\Queue::assertPushed(App\Jobs\ProcessChatStream::class, fn ($job): bool => $job->modelName === 'gpt-6.1-sol'
        && $job->agentModels[App\Ai\Agents\AgentRunner::class]['model'] === 'gpt-6.1-sol');
    expect($conversation->messages()->where('role', 'assistant')->sole()->chatStreamMeta()['agent_models'][App\Ai\Agents\AgentRunner::class]['model'])->toBe('gpt-6.1-sol');
});

it('restores model ownership and profiles after a failed delegated run', function (): void {
    $user = App\Models\User::factory()->create();
    $other = App\Models\User::factory()->create();
    $context = resolve(App\Services\Ai\AgentModelContext::class);
    $models = resolve(App\Services\Ai\ConfiguredAgentModels::class);
    $snapshot = $models->snapshot($user);
    $snapshot[App\Ai\Agents\NutritionSpecialist::class] = $models->forAgent(App\Ai\Agents\NutritionSpecialist::class, 'paid')->toArray();
    App\Ai\Agents\NutritionSpecialist::fake(['paid response', 'other response', 'standard response']);

    try {
        $context->run($user, $snapshot, function () use ($other): void {
            resolve(App\Ai\Agents\NutritionSpecialist::class)->prompt('paid task');
            resolve(App\Ai\Agents\NutritionSpecialist::class)->forModelUser($other)->prompt('other task');
            throw new RuntimeException('failed task');
        });
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('failed task');
    }
    resolve(App\Ai\Agents\NutritionSpecialist::class)->prompt('standard task');

    App\Ai\Agents\NutritionSpecialist::assertPrompted(fn ($prompt): bool => $prompt->prompt === 'paid task' && $prompt->model === 'gpt-6.1-sol');
    App\Ai\Agents\NutritionSpecialist::assertPrompted(fn ($prompt): bool => $prompt->prompt === 'other task' && $prompt->model === 'gemini-3.6-flash');
    App\Ai\Agents\NutritionSpecialist::assertPrompted(fn ($prompt): bool => $prompt->prompt === 'standard task' && $prompt->model === 'gemini-3.6-flash');
    expect($context->user())->toBeNull();
});

it('refuses a model that cannot produce the structured output required by an agent', function (): void {
    config()->set('ai.model_catalog.gpt-5-nano.capabilities', []);

    resolve(App\Services\Ai\ConfiguredAgentModels::class)->forAgent(ConversationTitleGeneratorAgent::class);
})->throws(InvalidArgumentException::class, 'does not support structured_output');

it('keeps the original provider and model when an approval resumes after a promotion', function (): void {
    $user = App\Models\User::factory()->create();
    $models = resolve(App\Services\Ai\ConfiguredAgentModels::class);
    $snapshot = $models->snapshot($user);
    $snapshot[App\Ai\Agents\AgentRunner::class] = $models->forModel('gemini-3.8-flash')->toArray();
    $history = App\Models\History::factory()->create([
        ...App\Models\Conversation::participantAttributes($user),
        'meta' => ['provider' => 'gemini', ...App\Models\History::streamMeta('stream-1', 'completed', [
            'model' => 'gemini-3.8-flash', 'agent_models' => $snapshot,
        ])],
    ]);
    config()->set('ai.agent_profiles.'.App\Ai\Agents\AgentRunner::class.'.standard', 'gpt-6.1-sol');

    $restored = $models->forTurn($history, $user);

    expect($restored[App\Ai\Agents\AgentRunner::class])->toMatchArray(['provider' => 'gemini', 'model' => 'gemini-3.8-flash']);
});

it('restores a dated provider snapshot from a legacy paused turn', function (): void {
    $user = App\Models\User::factory()->create();
    $history = new App\Models\History(['meta' => [
        'model' => 'gpt-5.4-mini-2026-03-17', 'provider' => 'openai',
    ]]);

    $profiles = resolve(App\Services\Ai\ConfiguredAgentModels::class)->forTurn($history, $user);

    expect($profiles[App\Ai\Agents\AgentRunner::class])->toMatchArray([
        'model' => 'gpt-5.4-mini-2026-03-17', 'provider' => 'openai',
    ]);
});
