<?php

declare(strict_types=1);

use App\Ai\Tools\GetDailyServingsByCalorie;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

covers(GetDailyServingsByCalorie::class);

it('returns daily servings content', function (): void {
    $tool = new GetDailyServingsByCalorie;

    $result = $tool->handle(new Request([]));

    expect($result)->toBeJson();

    $decoded = json_decode($result, true);

    expect($decoded['success'])->toBe(true)
        ->and($decoded['content'])->toContain('Daily Servings by Calorie Level');
});

it('has correct tool metadata', function (): void {
    $tool = new GetDailyServingsByCalorie;

    expect($tool->name())->toBe('get_daily_servings_by_calorie')
        ->and($tool->description())->toContain('USDA daily serving')
        ->and($tool->schema(new TestJsonSchema))->toBeArray()->not->toBeEmpty();
});
