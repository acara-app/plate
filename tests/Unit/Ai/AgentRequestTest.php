<?php

declare(strict_types=1);

use App\Ai\AgentRequest;
use App\Enums\ModelName;
use Laravel\Ai\Files\Base64Image;

covers(AgentRequest::class);

it('creates request with required parameters', function (): void {
    $request = new AgentRequest(
        message: 'Hello',
    );

    expect($request->message)->toBe('Hello')
        ->and($request->images)->toBe([])
        ->and($request->modelName)->toBeNull()
        ->and($request->conversationId)->toBeNull();
});

it('creates request with all parameters', function (): void {
    $images = [new Base64Image('abc123', 'image/jpeg')];
    $request = new AgentRequest(
        message: 'Hello',
        images: $images,
        modelName: ModelName::GPT_5_MINI,
        conversationId: 'conv-123',
    );

    expect($request->message)->toBe('Hello')
        ->and($request->images)->toHaveCount(1)
        ->and($request->modelName)->toBe(ModelName::GPT_5_MINI)
        ->and($request->conversationId)->toBe('conv-123');
});

describe('hasImages', function (): void {
    it('returns false when no images', function (): void {
        $request = new AgentRequest(message: 'Hello');

        expect($request->hasImages())->toBeFalse();
    });

    it('returns true when images present', function (): void {
        $request = new AgentRequest(
            message: 'Hello',
            images: [new Base64Image('abc123', 'image/jpeg')],
        );

        expect($request->hasImages())->toBeTrue();
    });
});

describe('hasExistingConversation', function (): void {
    it('returns false when conversation id is null', function (): void {
        $request = new AgentRequest(message: 'Hello');

        expect($request->hasExistingConversation())->toBeFalse();
    });

    it('returns true when conversation id is present', function (): void {
        $request = new AgentRequest(
            message: 'Hello',
            conversationId: 'conv-123',
        );

        expect($request->hasExistingConversation())->toBeTrue();
    });
});

describe('shouldEnableWebSearch', function (): void {
    it('returns false when model is null', function (): void {
        $request = new AgentRequest(message: 'Hello');

        expect($request->shouldEnableWebSearch())->toBeFalse();
    });

    it('returns true for GPT-5 models that support web search', function (): void {
        $request = new AgentRequest(
            message: 'Hello',
            modelName: ModelName::GPT_5_MINI,
        );

        expect($request->shouldEnableWebSearch())->toBeTrue();
    });

    it('returns false for Gemini models that do not support web search', function (): void {
        $request = new AgentRequest(
            message: 'Hello',
            modelName: ModelName::GEMINI_3_FLASH,
        );

        expect($request->shouldEnableWebSearch())->toBeFalse();
    });
});
