<?php

declare(strict_types=1);

use App\Enums\AgentMode;
use App\Enums\ModelName;
use App\Http\Requests\StoreAgentConversationRequest;
use Illuminate\Support\Facades\Validator;

$createRequest = function (array $data): StoreAgentConversationRequest {
    $request = new StoreAgentConversationRequest();

    // Merge data so it's available in all()
    $request->merge($data);

    // Run validation to populate validated data
    $validator = Validator::make($data, $request->rules());
    $request->setValidator($validator);

    if ($validator->fails()) {
        throw new Exception('Validation failed: '.json_encode($validator->errors()->all()));
    }

    return $request;
};

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
    ]);

    expect($request->userMessage())->toBe('Text content');
});

it('extracts mode and model from request', function () use ($createRequest): void {
    $request = $createRequest([
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hi']]]],
        'mode' => AgentMode::GenerateMealPlan->value,
        'model' => ModelName::GEMINI_2_5_FLASH->value,
    ]);

    expect($request->mode())->toBe(AgentMode::GenerateMealPlan)
        ->and($request->modelName())->toBe(ModelName::GEMINI_2_5_FLASH);
});
