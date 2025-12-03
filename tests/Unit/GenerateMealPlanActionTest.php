<?php

declare(strict_types=1);

use App\Utilities\JsonCleaner;

it('can extract valid JSON from markdown response', function (): void {
    $markdownResponse = "```json\n{\"type\": \"weekly\", \"name\": \"Test Plan\"}\n```";

    $result = JsonCleaner::extractAndValidateJson($markdownResponse);

    expect($result)->toBe('{"type": "weekly", "name": "Test Plan"}');
});

it('can extract valid JSON from response with preamble', function (): void {
    $responseWithPreamble = "Here's your meal plan:\n\n{\"type\": \"weekly\", \"name\": \"Test Plan\"}\n\nThis plan is great for weight loss.";

    $result = JsonCleaner::extractAndValidateJson($responseWithPreamble);

    expect($result)->toBe('{"type": "weekly", "name": "Test Plan"}');
});

it('throws exception for invalid JSON', function (): void {
    $invalidResponse = 'This is not JSON at all';

    JsonCleaner::extractAndValidateJson($invalidResponse);
})->throws(InvalidArgumentException::class, 'No valid JSON found in AI response');

it('throws exception for malformed JSON', function (): void {
    $malformedResponse = '{"type": "weekly", "name": "Test Plan"'; // Missing closing brace

    JsonCleaner::extractAndValidateJson($malformedResponse);
})->throws(JsonException::class);
