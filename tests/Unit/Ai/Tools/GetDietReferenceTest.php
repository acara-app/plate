<?php

declare(strict_types=1);

use App\Ai\Tools\GetDietReference;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

beforeEach(function (): void {
    $this->tool = new GetDietReference;
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('get_diet_reference')
        ->and($this->tool->description())->toContain('Fetch diet-specific reference materials');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;

    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['diet_type', 'reference_name']);
});

it('returns error for invalid diet type', function (): void {
    $request = new Request(['diet_type' => 'invalid_diet', 'reference_name' => 'food-list']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', false)
        ->and($json['error'])->toContain("Invalid diet type 'invalid_diet'");
});

it('returns error for invalid reference name', function (): void {
    $request = new Request(['diet_type' => 'keto', 'reference_name' => '../etc/passwd']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', false)
        ->and($json['error'])->toContain('Invalid reference name');
});

it('returns error if file does not exist', function (): void {
    $request = new Request(['diet_type' => 'keto', 'reference_name' => 'unknown-ref']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', false)
        ->and($json['error'])->toContain("Reference 'unknown-ref' not found");
});

it('returns content if file exists', function (): void {
    $path = resource_path('markdown/keto/references/test-food-list.md');

    // Ensure directory exists
    File::makeDirectory(dirname($path), 0755, true, true);

    // Create test file
    File::put($path, '# Keto Food List');

    $request = new Request(['diet_type' => 'keto', 'reference_name' => 'test-food-list']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    // Cleanup
    File::delete($path);

    expect($json)->toHaveKey('success', true)
        ->and($json['content'])->toBe('# Keto Food List');
});
