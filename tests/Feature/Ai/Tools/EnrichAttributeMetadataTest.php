<?php

declare(strict_types=1);

use App\Ai\Agents\EnrichAttributeMetadataAgent;
use App\Ai\Tools\EnrichAttributeMetadata;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

use function Pest\Laravel\actingAs;

covers(EnrichAttributeMetadata::class);

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

it('passes user preferred language to the agent when authenticated', function (): void {
    EnrichAttributeMetadataAgent::fake([[
        'safety_level' => 'info',
        'dietary_rules' => null,
        'foods_to_avoid' => null,
        'foods_to_prioritize' => null,
        'carb_limit_per_meal_g' => null,
        'min_fibre_per_meal_g' => null,
        'hidden_sources' => null,
        'requirements' => null,
        'general_advice' => 'ok',
    ]]);

    $agent = new EnrichAttributeMetadataAgent;
    app()->instance(EnrichAttributeMetadataAgent::class, $agent);

    $user = User::factory()->create(['locale' => 'en']);
    actingAs($user);

    $tool = new EnrichAttributeMetadata;
    $tool->handle(new Request([
        'category' => 'health_condition',
        'value' => 'Type 2 Diabetes',
    ]));

    expect($agent->instructions())
        ->toContain('language code: `en`')
        ->toContain('Write `dietary_rules`, `foods_to_avoid`');
});
