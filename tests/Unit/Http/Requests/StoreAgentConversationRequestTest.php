<?php

declare(strict_types=1);

use App\Enums\AgentMode;
use App\Enums\AgentType;
use App\Enums\ModelName;
use App\Http\Requests\StoreAgentConversationRequest;
use Illuminate\Support\Facades\Validator;

$createRequest = function (array $data): StoreAgentConversationRequest {
    $request = new StoreAgentConversationRequest();

    $request->merge($data);

    $validator = Validator::make($data, $request->rules());
    $request->setValidator($validator);

    if ($validator->fails()) {
        throw new Exception('Validation failed: '.json_encode($validator->errors()->all()));
    }

    return $request;
};

it('authorizes the request', function (): void {
    $request = new StoreAgentConversationRequest();
    expect($request->authorize())->toBeTrue();
});

it('returns custom validation messages', function (): void {
    $request = new StoreAgentConversationRequest();
    $messages = $request->messages();

    expect($messages)->toHaveKey('messages.required')
        ->and($messages['messages.required'])->toBe('Messages are required')
        ->and($messages)->toHaveKey('mode.required')
        ->and($messages['mode.required'])->toBe('Mode is required')
        ->and($messages)->toHaveKey('model.required')
        ->and($messages['model.required'])->toBe('Model is required')
        ->and($messages)->toHaveKey('agentType.required')
        ->and($messages['agentType.required'])->toBe('Agent type is required');
});

it('prepares data for validation by merging query parameters', function (): void {
    $uri = route('chat.stream', ['mode' => 'ask', 'model' => 'gpt-5-mini', 'agentType' => 'nutrition']);
    $request = StoreAgentConversationRequest::create(
        uri: $uri,
        method: 'POST'
    );

    $reflection = new ReflectionClass($request);
    $method = $reflection->getMethod('prepareForValidation');
    $method->invoke($request);

    expect($request->input('mode'))->toBe(AgentMode::Ask->value)
        ->and($request->input('model'))->toBe(ModelName::GPT_5_MINI->value)
        ->and($request->input('agentType'))->toBe(AgentType::Nutrition->value);
});

it('returns empty string if no user message is found', function () use ($createRequest): void {
    $request = $createRequest([
        'messages' => [
            [
                'role' => 'assistant',
                'parts' => [
                    ['type' => 'text', 'text' => 'Hello'],
                ],
            ],
        ],
        'mode' => AgentMode::Ask->value,
        'model' => ModelName::GPT_5_MINI->value,
        'agentType' => AgentType::Nutrition->value,
    ]);

    expect($request->userMessage())->toBe('');
});

it('extracts user message from conversation', function () use ($createRequest): void {
    $messages = [
        [
            'role' => 'user',
            'parts' => [
                ['type' => 'text', 'text' => 'Hello world'],
            ],
        ],
        [
            'role' => 'assistant',
            'parts' => [
                ['type' => 'text', 'text' => 'AI Response'],
            ],
        ],
    ];

    $request = $createRequest([
        'messages' => $messages,
        'mode' => AgentMode::Ask->value,
        'model' => ModelName::GPT_5_MINI->value,
        'agentType' => AgentType::Nutrition->value,
    ]);

    expect($request->userMessage())->toBe('Hello world');
});

it('ignores non-text parts when extracting user message', function () use ($createRequest): void {
    $messages = [
        [
            'role' => 'user',
            'parts' => [
                ['type' => 'image', 'image' => '...'],
                ['type' => 'text', 'text' => 'Text content'],
            ],
        ],
    ];

    $request = $createRequest([
        'messages' => $messages,
        'mode' => AgentMode::Ask->value,
        'model' => ModelName::GPT_5_MINI->value,
        'agentType' => AgentType::Nutrition->value,
    ]);

    expect($request->userMessage())->toBe('Text content');
});

it('extracts mode and model from request', function () use ($createRequest): void {
    $request = $createRequest([
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hi']]]],
        'mode' => AgentMode::CreateMealPlan->value,
        'model' => ModelName::GEMINI_2_5_FLASH->value,
        'agentType' => AgentType::HealthCoach->value,
    ]);

    expect($request->mode())->toBe(AgentMode::CreateMealPlan)
        ->and($request->modelName())->toBe(ModelName::GEMINI_2_5_FLASH)
        ->and($request->agentType())->toBe(AgentType::HealthCoach);
});
