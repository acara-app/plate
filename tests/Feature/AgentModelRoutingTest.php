<?php

declare(strict_types=1);

use App\Actions\CompletePendingChatStreamTurn;
use App\Actions\CreatePendingChatStreamTurn;
use App\Actions\GenerateConversationTitleAction;
use App\Actions\ProcessAdvisorMessageAction;
use App\Actions\SummarizeConversationAction;
use App\Ai\Agents\AgentRunner;
use App\Ai\Agents\CaffeineGuidanceAgent;
use App\Ai\Agents\ConversationSummarizerAgent;
use App\Ai\Agents\ConversationTitleGeneratorAgent;
use App\Ai\Agents\EnrichAttributeMetadataAgent;
use App\Ai\Agents\FitnessSpecialist;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Ai\Agents\GlucoseSpikeSpecialist;
use App\Ai\Agents\GroceryListAgent;
use App\Ai\Agents\HealthSpecialist;
use App\Ai\Agents\MealPlanAgent;
use App\Ai\Agents\MealPlanSpecialist;
use App\Ai\Agents\NutritionSpecialist;
use App\Ai\Agents\SingleMealAgent;
use App\Ai\Agents\SpikePredictorAgent;
use App\Contracts\Ai\UsesAgentModelProfiles;
use App\Data\ChatStreamResult;
use App\Jobs\ProcessChatStream;
use App\Models\AiUsage;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use App\Services\Ai\AgentModelContext;
use App\Services\Ai\ConfiguredAgentModels;
use App\Services\Ai\PlateConversationStore;
use App\Services\StreamEventStore;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\AiManager;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;

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
    AgentRunner::class,
    FitnessSpecialist::class,
    HealthSpecialist::class,
    NutritionSpecialist::class,
    MealPlanSpecialist::class,
    GlucoseSpikeSpecialist::class,
    CaffeineGuidanceAgent::class,
    GroceryListAgent::class,
    MealPlanAgent::class,
    SingleMealAgent::class,
    SpikePredictorAgent::class,
    FoodPhotoAnalyzerAgent::class,
    ConversationTitleGeneratorAgent::class,
    ConversationSummarizerAgent::class,
    EnrichAttributeMetadataAgent::class,
]);

it('freezes the resolved models when a chat turn is queued', function (): void {
    Queue::fake();
    $this->mock(StreamEventStore::class)->shouldReceive('clear')->once();
    config()->set('ai.agent_profiles.'.AgentRunner::class.'.standard', 'gpt-6.1-sol');
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(Conversation::participantAttributes($user));

    $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello']]]],
    ])->assertAccepted();
    config()->set('ai.agent_profiles.'.AgentRunner::class.'.standard', 'gpt-5.4-mini');

    Queue::assertPushed(ProcessChatStream::class, fn ($job): bool => $job->modelName === 'gpt-6.1-sol'
        && $job->agentModels[AgentRunner::class]['model'] === 'gpt-6.1-sol');
    expect($conversation->messages()->where('role', 'assistant')->sole()->chatStreamMeta())->not->toHaveKey('agent_models');
});

it('restores model ownership and profiles after a failed delegated run', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $context = resolve(AgentModelContext::class);
    $models = resolve(ConfiguredAgentModels::class);
    $snapshot = $models->snapshot($user);
    $snapshot[NutritionSpecialist::class] = $models->forAgent(NutritionSpecialist::class, 'paid')->toArray();
    NutritionSpecialist::fake(['paid response', 'other response', 'standard response']);

    try {
        $context->run($user, $snapshot, function () use ($other): void {
            resolve(NutritionSpecialist::class)->prompt('paid task');
            resolve(NutritionSpecialist::class)->forModelUser($other)->prompt('other task');
            throw new RuntimeException('failed task');
        });
    } catch (RuntimeException $runtimeException) {
        expect($runtimeException->getMessage())->toBe('failed task');
    }

    resolve(NutritionSpecialist::class)->prompt('standard task');

    NutritionSpecialist::assertPrompted(fn ($prompt): bool => $prompt->prompt === 'paid task' && $prompt->model === 'gpt-6.1-sol');
    NutritionSpecialist::assertPrompted(fn ($prompt): bool => $prompt->prompt === 'other task' && $prompt->model === 'gemini-3.6-flash');
    NutritionSpecialist::assertPrompted(fn ($prompt): bool => $prompt->prompt === 'standard task' && $prompt->model === 'gemini-3.6-flash');
    expect($context->user())->toBeNull();
});

it('refuses a model that cannot produce the structured output required by an agent', function (): void {
    config()->set('ai.model_catalog.gpt-5-nano.capabilities', []);

    resolve(ConfiguredAgentModels::class)->forAgent(ConversationTitleGeneratorAgent::class);
})->throws(InvalidArgumentException::class, 'does not support structured_output');

it('keeps the original provider and model when an approval resumes after a promotion', function (): void {
    $user = User::factory()->create();
    $models = resolve(ConfiguredAgentModels::class);
    $snapshot = $models->snapshot($user);
    $snapshot[AgentRunner::class] = $models->forModel('gemini-3.8-flash')->toArray();
    $history = History::factory()->create([
        ...Conversation::participantAttributes($user),
        'meta' => ['provider' => 'gemini', ...History::streamMeta('stream-1', 'completed', [
            'model' => 'gemini-3.8-flash', 'agent_models' => $snapshot,
        ])],
    ]);
    config()->set('ai.agent_profiles.'.AgentRunner::class.'.standard', 'gpt-6.1-sol');

    $restored = $models->forTurn($history, $user);

    expect($restored[AgentRunner::class])->toMatchArray(['provider' => 'gemini', 'model' => 'gemini-3.8-flash']);
});

it('restores a dated provider snapshot from a legacy paused turn', function (): void {
    $user = User::factory()->create();
    $history = new History(['meta' => [
        'model' => 'gpt-5.4-mini-2026-03-17', 'provider' => 'openai',
    ]]);

    $profiles = resolve(ConfiguredAgentModels::class)->forTurn($history, $user);

    expect($profiles[AgentRunner::class])->toMatchArray([
        'model' => 'gpt-5.4-mini-2026-03-17', 'provider' => 'openai',
    ]);
});

it('attributes background conversation work to its owner rather than the current session', function (string $task): void {
    $owner = User::factory()->create();
    $this->actingAs(User::factory()->create());
    $conversation = Conversation::factory()->forUser($owner)->create(['title' => Conversation::DEFAULT_TITLE]);
    History::factory()->forConversation($conversation)->create(['role' => 'user', 'content' => 'Plan lunches']);
    config()->set('altani.summarization.buffer', 0);
    config()->set('altani.summarization.threshold', 1);

    $agent = $task === 'title' ? ConversationTitleGeneratorAgent::class : ConversationSummarizerAgent::class;
    $agent::fake([[
        'title' => 'Lunch plans', 'summary' => 'Lunch plans', 'topics' => [],
        'key_facts' => [], 'unresolved_threads' => [], 'resolved_threads' => [],
    ]]);

    resolve($task === 'title' ? GenerateConversationTitleAction::class : SummarizeConversationAction::class)->handle($conversation);

    expect(AiUsage::query()->where('agent', $agent)->sole())->user_id->toBe($owner->id);
    expect(resolve(AgentModelContext::class)->user())->toBeNull();
})->with(['title', 'summary']);

it('runs a chat job queued before agent models were frozen on the model it was queued with', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(Conversation::participantAttributes($user));
    $turn = resolve(CreatePendingChatStreamTurn::class)->handle($conversation, $user, 'Hello', [], 'web', 'gpt-5-mini');
    $queued = new ProcessChatStream($user->id, $conversation->id, 'gpt-5-mini', 'web', $turn->streamId, $turn->userMessageId, $turn->assistantMessageId);
    $legacy = new ReflectionClass(ProcessChatStream::class)->newInstanceWithoutConstructor();
    $legacy->__unserialize(Arr::except($queued->__serialize(), 'agentModels'));
    AgentRunner::fake(['Hello back']);

    app()->call($legacy->handle(...));

    AgentRunner::assertPrompted(fn ($prompt): bool => $prompt->model === 'gpt-5-mini');
});

it('keeps chat available when an unrelated agent profile is misconfigured', function (): void {
    config()->set('ai.agent_profiles.'.EnrichAttributeMetadataAgent::class.'.standard', 'unregistered-model');

    $snapshot = resolve(ConfiguredAgentModels::class)->snapshot(User::factory()->create());

    expect($snapshot)->toHaveKey(AgentRunner::class)
        ->not->toHaveKey(EnrichAttributeMetadataAgent::class);
});

it('resumes a legacy paused turn on the current chat model when its recorded model is retired', function (): void {
    $user = User::factory()->create();
    $history = new History(['meta' => ['model' => 'gpt-4-retired', 'provider' => 'openai']]);

    $profiles = resolve(ConfiguredAgentModels::class)->forTurn($history, $user);

    expect($profiles[AgentRunner::class])->toMatchArray(['model' => config('ai.agent_profiles.'.AgentRunner::class.'.standard'), 'provider' => 'openai']);
});

it('resumes a turn created without a frozen snapshot on the configured chat model', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(Conversation::participantAttributes($user));
    $turn = resolve(CreatePendingChatStreamTurn::class)->forResume($conversation, $user, 'web');

    $profiles = resolve(ConfiguredAgentModels::class)->forTurn(History::query()->findOrFail($turn->assistantMessageId), $user);

    expect($profiles[AgentRunner::class])->toMatchArray(['model' => config('ai.agent_profiles.'.AgentRunner::class.'.standard')]);
});

it('resumes a telegram approval on the models frozen for the turn awaiting it', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(Conversation::participantAttributes($user));
    $models = resolve(ConfiguredAgentModels::class);
    $pausedWith = function (string $model, string $callId) use ($conversation, $user, $models): void {
        $snapshot = $models->snapshot($user);
        $snapshot[AgentRunner::class] = $models->forModel($model)->toArray();
        History::factory()->forConversation($conversation)->awaitingApproval([$callId => 'Glucose 140 mg/dL'])->create([
            'meta' => History::streamMeta('stream-'.$callId, 'completed', ['agent_models' => $snapshot]),
        ]);
    };
    $pausedWith('gemini-3.8-flash', 'call_old');
    $pausedWith('gpt-6.1-sol', 'call_new');
    AgentRunner::fake(['Logged.']);

    resolve(ProcessAdvisorMessageAction::class)->resume($user, $conversation->id, Decisions::from(['call_new' => true]));

    AgentRunner::assertPrompted(fn ($prompt): bool => $prompt->model === 'gpt-6.1-sol');
});

it('keeps an explicitly selected model when a task agent is run for a user', function (): void {
    SpikePredictorAgent::fake([[
        'risk_level' => 'low', 'estimated_gl' => 5, 'explanation' => 'Fibre slows absorption.',
        'smart_fix' => 'Add protein.', 'spike_reduction_percentage' => 10,
    ]]);
    $profile = resolve(ConfiguredAgentModels::class)->forModel('gemini-3.8-flash');

    resolve(SpikePredictorAgent::class)->usingAgentModel($profile)->predict('Lentil soup', User::factory()->create());

    SpikePredictorAgent::assertPrompted(fn ($prompt): bool => $prompt->model === 'gemini-3.8-flash');
});

it('records only the provider and model of each agent when a streamed turn pauses for approval', function (array $pendingApprovals, ?array $recorded): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    $assistant = History::factory()->forConversation($conversation)->create([
        'role' => 'assistant', 'meta' => History::streamMeta('stream-1', History::STREAM_STATUS_PENDING),
    ]);
    $snapshot = resolve(ConfiguredAgentModels::class)->snapshot($user, [AgentRunner::class => ['model' => 'gpt-6.1-sol']]);

    resolve(AgentModelContext::class)->run($user, $snapshot, fn () => resolve(CompletePendingChatStreamTurn::class)->handle(
        conversationId: $conversation->id,
        user: $user,
        userMessageId: null,
        assistantMessageId: $assistant->id,
        result: new ChatStreamResult(
            text: 'Confirm below.',
            toolCalls: [['id' => 'call_abc', 'name' => 'log_health_entry', 'arguments' => []]],
            pendingApprovals: $pendingApprovals,
        ),
        status: History::STREAM_STATUS_COMPLETED,
    ));

    expect($assistant->fresh()->chatStreamMeta()['agent_models'][AgentRunner::class] ?? null)->toBe($recorded);
})->with([
    'paused' => [['call_abc' => 'Glucose 140 mg/dL'], ['provider' => 'openai', 'model' => 'gpt-6.1-sol']],
    'completed' => [[], null],
]);

it('records the turn models on a telegram reply that pauses for approval', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    $prompt = new AgentPrompt(resolve(AgentRunner::class), 'Log 140', [], resolve(AiManager::class)->textProvider('openai'), 'gpt-6.1-sol');
    $response = new AgentResponse('invocation-1', 'Confirm below.', new TextUsage, new Meta('openai', 'gpt-6.1-sol-2026-09-01'));
    $response->withPendingApprovals(collect([new PendingApproval('call_abc', 'log_health_entry', [], 'Glucose 140 mg/dL')]));

    $snapshot = resolve(ConfiguredAgentModels::class)->snapshot($user, [AgentRunner::class => ['model' => 'gpt-6.1-sol']]);

    $id = resolve(AgentModelContext::class)->run($user, $snapshot, fn (): ?string => resolve(PlateConversationStore::class)
        ->storeAssistantMessage($conversation->id, $user->getMorphClass(), $user->id, $prompt, $response));

    expect(History::query()->findOrFail($id)->chatStreamMeta()['agent_models'][AgentRunner::class])->toBe(['provider' => 'openai', 'model' => 'gpt-6.1-sol']);
});

it('ships a registered, capable and priced model for every agent profile', function (): void {
    $models = resolve(ConfiguredAgentModels::class);

    foreach (config()->array('ai.agent_profiles') as $agentClass => $profiles) {
        expect(is_a($agentClass, UsesAgentModelProfiles::class, true))->toBeTrue();

        foreach (array_keys($profiles) as $profile) {
            expect($models->forAgent($agentClass, $profile)->pricing['input'])->toBeGreaterThan(0);
        }
    }
});
