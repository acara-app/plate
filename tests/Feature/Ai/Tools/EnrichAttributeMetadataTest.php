<?php

declare(strict_types=1);

use App\Ai\Tools\EnrichAttributeMetadata;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

it('has correct name and description', function (): void {
    $tool = new EnrichAttributeMetadata;

    expect($tool->name())->toBe('enrich_attribute_metadata')
        ->and($tool->description())->toContain('dietary metadata');
});

it('validates schema structure', function (): void {
    $tool = new EnrichAttributeMetadata;
    $schema = new TestJsonSchema;

    $result = $tool->schema($schema);

    expect($result)->toBeArray()
        ->toHaveKeys(['category', 'value']);
});

it('throws exception when category is missing', function (): void {
    $tool = new EnrichAttributeMetadata;

    $request = new Request([
        'value' => 'Test',
    ]);

    $this->expectException(RuntimeException::class);

    $tool->handle($request);
});

it('throws exception when value is missing', function (): void {
    $tool = new EnrichAttributeMetadata;

    $request = new Request([
        'category' => 'health_condition',
    ]);

    $this->expectException(RuntimeException::class);

    $tool->handle($request);
});
