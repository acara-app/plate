<?php

declare(strict_types=1);

use App\Ai\Tools\GetDietReference;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

covers(GetDietReference::class);

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

it('returns the available choices for an invalid diet type', function (mixed $dietType): void {
    $request = new Request(['diet_type' => $dietType, 'reference_name' => 'food-list']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', false)
        ->and($json['error'])->toContain('Invalid diet type', 'mediterranean');
})->with([
    'unknown diet' => ['invalid_diet'],
    'multiple diets' => [['keto', 'paleo']],
]);

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

it('reads the requested diet guide or supplementary reference', function (array $arguments, string $heading): void {
    $result = json_decode($this->tool->handle(new Request($arguments)), true, flags: JSON_THROW_ON_ERROR);

    expect($result)->toMatchArray([
        'success' => true,
        'diet_type' => $arguments['diet_type'],
        'reference_name' => $arguments['reference_name'] ?? null,
    ])->and($result['content'])->toContain($heading);
})->with([
    'diet guide' => [['diet_type' => 'dash'], '# DASH Diet'],
    'diet guide with a null reference' => [['diet_type' => 'keto', 'reference_name' => null], '# Ketogenic Diet'],
    'named supplementary file' => [['diet_type' => 'mediterranean', 'reference_name' => 'med-diet-nutrient-score-card'], 'The Mediterranean Diet Nutrient Score Card'],
]);
