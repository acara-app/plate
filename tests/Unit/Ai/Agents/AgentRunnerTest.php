<?php

declare(strict_types=1);

use App\Ai\AgentBuilder;
use App\Ai\AgentRequest;
use App\Ai\Agents\AgentRunner;
use App\Ai\Agents\FitnessSpecialist;
use App\Ai\Agents\GlucoseSpikeSpecialist;
use App\Ai\Agents\HealthSpecialist;
use App\Ai\Agents\MealPlanSpecialist;
use App\Ai\Agents\NutritionSpecialist;
use App\Ai\Tools\AnalyzePhoto;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\LogHealthEntry;
use App\Data\Ai\AgentModelData;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\ToolResultMessage;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;

covers(AgentRunner::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->agentBuilder = resolve(AgentBuilder::class);
    $this->agent = resolve(AgentRunner::class);
});

describe('instructions', function (): void {
    it('returns instructions with default mode', function (): void {
        $request = new AgentRequest(message: 'Hello', model: new AgentModelData('openai', 'gpt-5.4-mini'));

        $this->agent->run($request, $this->user);
        $instructions = $this->agent->instructions();

        expect($instructions)
            ->toContain('You are Altani, a comprehensive AI wellness assistant')
            ->toContain('call `get_user_profile`');
    });

    it('returns instructions that delegate meal planning to a specialist', function (): void {
        $request = new AgentRequest(message: 'Create a meal plan', model: new AgentModelData('openai', 'gpt-5.4-mini'));

        $this->agent->run($request, $this->user);
        $instructions = $this->agent->instructions();

        expect($instructions)
            ->toContain('You are Altani, a comprehensive AI wellness assistant')
            ->toContain('meal_plan_specialist');
    });
});

describe('tools', function (): void {
    it('can recover when a model calls the retired skill tool', function (): void {
        $conversation = Conversation::factory()->forUser($this->user)->create();
        $arguments = ['name' => 'nutrition-analyzer'];
        AgentRunner::fake([
            new ToolCall('old-tool', 'activate_skill', $arguments),
            new ToolCall('current-tool', 'LoadSkill', $arguments),
            'Review complete.',
        ])->preventStrayPrompts();

        $response = $this->agent->runSync(new AgentRequest(
            message: 'Review my nutrition.',
            conversationId: $conversation->id,
        ), $this->user);

        [$retired, $loaded] = $response->toolResults->all();

        expect($retired->failed)->toBeTrue()
            ->and($retired->result)->toContain('LoadSkill')
            ->and($loaded->result)->toContain('<skill_content name="nutrition-analyzer">')
            ->and($response->text)->toBe('Review complete.');
    });

    it('returns top-level tools plus specialist sub-agents', function (): void {
        $request = new AgentRequest(message: 'Hello', model: new AgentModelData('openai', 'gpt-5.4-mini'));

        $this->agent->run($request, $this->user);
        $tools = $this->agent->tools();

        $toolClasses = collect($tools)
            ->map(fn (mixed $t): string => $t::class)
            ->all();

        expect($toolClasses)
            ->toContain(GetUserProfile::class)
            ->toContain(LogHealthEntry::class)
            ->toContain(MealPlanSpecialist::class)
            ->toContain(NutritionSpecialist::class)
            ->toContain(GlucoseSpikeSpecialist::class)
            ->toContain(HealthSpecialist::class)
            ->toContain(FitnessSpecialist::class);
    });

    it('includes AnalyzePhoto tool when attachments are set', function (): void {
        $image = new Base64Image(base64_encode('fake-image'), 'image/jpeg');
        $request = new AgentRequest(message: 'Analyze this', images: [$image], model: new AgentModelData('openai', 'gpt-5.4-mini'));

        $this->agent->run($request, $this->user);
        $tools = $this->agent->tools();

        $toolClasses = collect($tools)
            ->map(fn (mixed $t): string => $t::class)
            ->all();

        expect($toolClasses)->toContain(AnalyzePhoto::class);
    });

    it('includes provider tools when web search enabled', function (): void {
        $request = new AgentRequest(message: 'Search for something', model: new AgentModelData('openai', 'gpt-5-mini'));

        $this->agent->run($request, $this->user);
        $tools = $this->agent->tools();

        expect($tools)->not->toBeEmpty();
    });
});

describe('messages', function (): void {
    it('replays the latest eligible history rows with their tool results intact', function (int $limit, int $placeholders): void {
        config(['altani.context.history_limit' => $limit]);

        $conversation = Conversation::factory()->forUser($this->user)->create();
        $history = History::factory()->forConversation($conversation);
        $history->userMessage()->create(['content' => 'Review my nutrition.']);

        $call = new ToolCall('skill-call', 'LoadSkill', ['name' => 'nutrition-analyzer']);
        $result = new ToolResult($call->id, $call->name, $call->arguments, 'Nutrition guidance');

        $history->assistantMessage()->create([
            'steps' => [['content' => '', 'tool_calls' => [[...$call->toArray(), 'result' => $result->result]]]],
        ]);
        $history->pendingStream()->count($placeholders)->create();

        $this->agent->run(new AgentRequest(message: 'Continue', conversationId: $conversation->id), $this->user);

        expect($this->agent->messages())->toEqual($limit === 0 ? [] : [
            new AssistantMessage('', collect([$call])),
            new ToolResultMessage(collect([$result])),
        ]);
    })->with([
        'no placeholders' => [1, 0],
        'several pending streams' => [1, 3],
        'disabled history' => [0, 0],
    ]);

    it('returns empty messages when no conversation', function (): void {
        $request = new AgentRequest(message: 'Hello', model: new AgentModelData('openai', 'gpt-5.4-mini'));

        $this->agent->run($request, $this->user);
        $messages = $this->agent->messages();

        expect($messages)->toBeArray()
            ->toHaveCount(0);
    });

    it('excludes the pending turn for the active stream from conversation context', function (): void {
        $conversation = Conversation::factory()->forUser($this->user)->create();

        History::factory()->forConversation($conversation)->userMessage()->create([
            'content' => 'Previous question',
        ]);
        History::factory()->forConversation($conversation)->assistantMessage()->create([
            'content' => 'Previous answer',
            'steps' => [['content' => 'Previous answer', 'tool_calls' => [], 'reasoning' => '', 'replay_blocks' => [], 'provider_tool_calls' => []]],
        ]);
        History::factory()->forConversation($conversation)->userMessage()->create([
            'content' => 'Current question',
            'meta' => History::streamMeta('stream-1', History::STREAM_STATUS_SUBMITTED),
        ]);
        History::factory()->forConversation($conversation)->assistantMessage()->create([
            'content' => '',
            'meta' => History::streamMeta('stream-1', History::STREAM_STATUS_PENDING),
        ]);

        $request = new AgentRequest(
            message: 'Current question',
            conversationId: $conversation->id,
            streamId: 'stream-1',
            model: new AgentModelData('openai', 'gpt-5.4-mini'),
        );

        $this->agent->run($request, $this->user);
        $messages = $this->agent->messages();

        expect($messages)->toHaveCount(2)
            ->and($messages[0])->toBeInstanceOf(Message::class)
            ->and($messages[0]->content)->toBe('Previous question')
            ->and($messages[1])->toBeInstanceOf(AssistantMessage::class)
            ->and($messages[1]->content)->toBe('Previous answer');
    });
});
