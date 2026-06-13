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
use App\Enums\ModelName;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\Message;

covers(AgentRunner::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->agentBuilder = resolve(AgentBuilder::class);
    $this->agent = resolve(AgentRunner::class);
});

describe('instructions', function (): void {
    it('returns instructions with default mode', function (): void {
        $request = new AgentRequest(message: 'Hello', modelName: ModelName::GPT_5_4_MINI);

        $this->agent->run($request, $this->user);
        $instructions = $this->agent->instructions();

        expect($instructions)
            ->toContain('You are Altani, a comprehensive AI wellness assistant')
            ->toContain('call `get_user_profile`');
    });

    it('returns instructions that delegate meal planning to a specialist', function (): void {
        $request = new AgentRequest(message: 'Create a meal plan', modelName: ModelName::GPT_5_4_MINI);

        $this->agent->run($request, $this->user);
        $instructions = $this->agent->instructions();

        expect($instructions)
            ->toContain('You are Altani, a comprehensive AI wellness assistant')
            ->toContain('meal_plan_specialist');
    });
});

describe('tools', function (): void {
    it('returns top-level tools plus specialist sub-agents', function (): void {
        $request = new AgentRequest(message: 'Hello', modelName: ModelName::GPT_5_4_MINI);

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
        $request = new AgentRequest(message: 'Analyze this', images: [$image], modelName: ModelName::GPT_5_4_MINI);

        $this->agent->run($request, $this->user);
        $tools = $this->agent->tools();

        $toolClasses = collect($tools)
            ->map(fn (mixed $t): string => $t::class)
            ->all();

        expect($toolClasses)->toContain(AnalyzePhoto::class);
    });

    it('includes provider tools when web search enabled', function (): void {
        $request = new AgentRequest(message: 'Search for something', modelName: ModelName::GPT_5_MINI);

        $this->agent->run($request, $this->user);
        $tools = $this->agent->tools();

        expect($tools)->not->toBeEmpty();
    });
});

describe('messages', function (): void {
    it('returns empty messages when no conversation', function (): void {
        $request = new AgentRequest(message: 'Hello', modelName: ModelName::GPT_5_4_MINI);

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
            modelName: ModelName::GPT_5_4_MINI,
            conversationId: $conversation->id,
            streamId: 'stream-1',
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

describe('providerOptions', function (): void {
    it('requests Gemini thinking config for a thinking-capable model', function (): void {
        $request = new AgentRequest(message: 'Hello', modelName: ModelName::GEMINI_3_5_FLASH);

        $this->agent->run($request, $this->user);

        expect($this->agent->providerOptions(Lab::Gemini))->toBe([
            'thinkingConfig' => [
                'thinkingBudget' => 8192,
                'includeThoughts' => true,
            ],
        ]);
    });
});
