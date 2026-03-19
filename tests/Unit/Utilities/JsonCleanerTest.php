<?php

declare(strict_types=1);

use App\Utilities\JsonCleaner;

it('extracts json from markdown code blocks', function (): void {
    $input = '```json
{"key": "value"}
```';

    expect(JsonCleaner::extractAndValidateJson($input))->toBe('{"key": "value"}');
});

it('returns raw json when already valid', function (): void {
    $input = '{"key": "value"}';

    expect(JsonCleaner::extractAndValidateJson($input))->toBe('{"key": "value"}');
});

it('extracts json embedded in surrounding text', function (): void {
    $input = 'Here is the result: {"key": "value"} and some more text';

    expect(JsonCleaner::extractAndValidateJson($input))->toBe('{"key": "value"}');
});

it('extracts json array embedded in surrounding text', function (): void {
    $input = 'Result: ["one", "two"] done';

    expect(JsonCleaner::extractAndValidateJson($input))->toBe('["one", "two"]');
});

it('throws InvalidArgumentException when no json found', function (): void {
    JsonCleaner::extractAndValidateJson('no json here at all');
})->throws(InvalidArgumentException::class, 'No valid JSON found in AI response');

it('throws JsonException when json is malformed', function (): void {
    JsonCleaner::extractAndValidateJson('{"key": broken}');
})->throws(JsonException::class);

it('trims whitespace around json', function (): void {
    $input = '   {"key": "value"}   ';

    expect(JsonCleaner::extractAndValidateJson($input))->toBe('{"key": "value"}');
});
