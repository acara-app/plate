<?php

declare(strict_types=1);

use App\Enums\AgentMode;
use App\Enums\ModelName;
use App\Http\Requests\StreamChatRequest;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Files\Base64Image;

covers(StreamChatRequest::class);

$createRequest = function (array $data): StreamChatRequest {
    $request = new StreamChatRequest();

    $request->merge($data);

    $validator = Validator::make($data, $request->rules());
    $request->setValidator($validator);

    if ($validator->fails()) {
        throw new Exception('Validation failed: '.json_encode($validator->errors()->all()));
    }

    return $request;
};

it('authorizes the request', function (): void {
    $request = new StreamChatRequest();
    expect($request->authorize())->toBeTrue();
});

it('returns custom validation messages', function (): void {
    $request = new StreamChatRequest();
    $messages = $request->messages();

    expect($messages)->toHaveKey('messages.required')
        ->and($messages['messages.required'])->toBe('Messages are required')
        ->and($messages)->toHaveKey('mode.required')
        ->and($messages['mode.required'])->toBe('Mode is required')
        ->and($messages)->toHaveKey('model.required')
        ->and($messages['model.required'])->toBe('Model is required');
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
        'mode' => AgentMode::CreateMealPlan->value,
        'model' => ModelName::GPT_5_4_MINI->value,
    ]);

    expect($request->mode())->toBe(AgentMode::CreateMealPlan)
        ->and($request->modelName())->toBe(ModelName::GPT_5_4_MINI);
});

it('returns empty attachments when no file parts exist', function () use ($createRequest): void {
    $request = $createRequest([
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hi']]]],
        'mode' => AgentMode::Ask->value,
        'model' => ModelName::GPT_5_MINI->value,
    ]);

    expect($request->userAttachments())->toBe([]);
});

it('extracts image attachments from user message', function () use ($createRequest): void {
    $base64Content = base64_encode('fake-image-data');
    $dataUrl = 'data:image/jpeg;base64,'.$base64Content;

    $request = $createRequest([
        'messages' => [
            [
                'role' => 'user',
                'parts' => [
                    ['type' => 'text', 'text' => 'What is this food?'],
                    ['type' => 'file', 'mediaType' => 'image/jpeg', 'url' => $dataUrl],
                ],
            ],
        ],
        'mode' => AgentMode::Ask->value,
        'model' => ModelName::GPT_5_MINI->value,
    ]);

    $attachments = $request->userAttachments();

    expect($attachments)->toHaveCount(1)
        ->and($attachments[0])->toBeInstanceOf(Base64Image::class)
        ->and($attachments[0]->base64)->toBe($base64Content)
        ->and($attachments[0]->mime)->toBe('image/jpeg');
});

it('ignores non-image file parts in attachments', function () use ($createRequest): void {
    $request = $createRequest([
        'messages' => [
            [
                'role' => 'user',
                'parts' => [
                    ['type' => 'text', 'text' => 'Check this'],
                    ['type' => 'file', 'mediaType' => 'application/pdf', 'url' => 'data:application/pdf;base64,abc123'],
                ],
            ],
        ],
        'mode' => AgentMode::Ask->value,
        'model' => ModelName::GPT_5_MINI->value,
    ]);

    expect($request->userAttachments())->toBe([]);
});
