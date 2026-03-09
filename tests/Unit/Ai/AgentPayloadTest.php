<?php

declare(strict_types=1);

use App\Ai\AgentPayload;
use App\Enums\AgentMode;
use App\Enums\ModelName;
use Laravel\Ai\Files\Base64Image;

it('creates payload with required parameters', function (): void {
    $payload = new AgentPayload(
        userId: 1,
        message: 'Hello',
    );

    expect($payload->userId)->toBe(1)
        ->and($payload->message)->toBe('Hello')
        ->and($payload->images)->toBe([])
        ->and($payload->mode)->toBe(AgentMode::Ask)
        ->and($payload->modelName)->toBeNull();
});

it('creates payload with all parameters', function (): void {
    $images = [new Base64Image('abc123', 'image/jpeg')];
    $payload = new AgentPayload(
        userId: 1,
        message: 'Hello',
        images: $images,
        mode: AgentMode::CreateMealPlan,
        modelName: ModelName::GPT_5_MINI,
    );

    expect($payload->userId)->toBe(1)
        ->and($payload->message)->toBe('Hello')
        ->and($payload->images)->toHaveCount(1)
        ->and($payload->mode)->toBe(AgentMode::CreateMealPlan)
        ->and($payload->modelName)->toBe(ModelName::GPT_5_MINI);
});

describe('hasImages', function (): void {
    it('returns false when no images', function (): void {
        $payload = new AgentPayload(userId: 1, message: 'Hello');

        expect($payload->hasImages())->toBeFalse();
    });

    it('returns true when images present', function (): void {
        $payload = new AgentPayload(
            userId: 1,
            message: 'Hello',
            images: [new Base64Image('abc123', 'image/jpeg')],
        );

        expect($payload->hasImages())->toBeTrue();
    });
});

describe('shouldEnableWebSearch', function (): void {
    it('returns false when model is null', function (): void {
        $payload = new AgentPayload(userId: 1, message: 'Hello');

        expect($payload->shouldEnableWebSearch())->toBeFalse();
    });

    it('returns true for GPT-5 models that support web search', function (): void {
        $payload = new AgentPayload(
            userId: 1,
            message: 'Hello',
            modelName: ModelName::GPT_5_MINI,
        );

        expect($payload->shouldEnableWebSearch())->toBeTrue();
    });

    it('returns false for Gemini models that do not support web search', function (): void {
        $payload = new AgentPayload(
            userId: 1,
            message: 'Hello',
            modelName: ModelName::GEMINI_2_5_FLASH,
        );

        expect($payload->shouldEnableWebSearch())->toBeFalse();
    });
});
