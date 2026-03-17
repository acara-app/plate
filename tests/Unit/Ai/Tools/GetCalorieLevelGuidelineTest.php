<?php

declare(strict_types=1);

use App\Ai\Tools\GetCalorieLevelGuideline;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;

it('returns dietary guidelines content', function (): void {
    $tool = new GetCalorieLevelGuideline;

    $result = $tool->handle(new Request([]));

    expect($result)->toBeJson();

    $decoded = json_decode($result, true);

    expect($decoded['success'])->toBe(true)
        ->and($decoded['content'])->toContain('Dietary Guidelines for Americans');
});

it('has correct tool metadata', function (): void {
    $tool = new GetCalorieLevelGuideline;

    expect($tool->name())->toBe('get_calorie_level_guideline')
        ->and($tool->description())->toContain('USDA Dietary Guidelines')
        ->and($tool->schema(Mockery::mock(JsonSchema::class)))->toBe([]);
});
