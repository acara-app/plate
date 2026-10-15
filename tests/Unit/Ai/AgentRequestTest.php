<?php

declare(strict_types=1);

use App\Ai\AgentRequest;
use App\Services\Ai\ConfiguredAgentModels;
use Laravel\Ai\Files\Base64Image;

covers(AgentRequest::class);

it('creates request with required parameters', function (): void {
    $request = new AgentRequest(
        message: 'Hello',
    );

    expect($request->message)->toBe('Hello')
        ->and($request->images)->toBe([])
        ->and($request->model)->toBeNull()
        ->and($request->conversationId)->toBeNull();
});

it('creates request with all parameters', function (): void {
    $images = [new Base64Image('abc123', 'image/jpeg')];
    $request = new AgentRequest(
        message: 'Hello',
        images: $images,
        conversationId: 'conv-123',
        streamId: 'stream-123',
        model: resolve(ConfiguredAgentModels::class)->forModel('gpt-5-mini'),
    );

    expect($request->message)->toBe('Hello')
        ->and($request->images)->toHaveCount(1)
        ->and($request->model?->model)->toBe('gpt-5-mini')
        ->and($request->conversationId)->toBe('conv-123')
        ->and($request->streamId)->toBe('stream-123');
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
            model: resolve(ConfiguredAgentModels::class)->forModel('gpt-5-mini'),
        );

        expect($request->shouldEnableWebSearch())->toBeTrue();
    });

    it('returns false for Gemini models that do not support web search', function (): void {
        $request = new AgentRequest(
            message: 'Hello',
            model: resolve(ConfiguredAgentModels::class)->forModel('gemini-3.5-flash'),
        );

        expect($request->shouldEnableWebSearch())->toBeFalse();
    });
});
