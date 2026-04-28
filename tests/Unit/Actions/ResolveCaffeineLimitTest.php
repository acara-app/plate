<?php

declare(strict_types=1);

use App\Actions\ResolveCaffeineLimit;

covers(ResolveCaffeineLimit::class);

it('resolves deterministic caffeine limits', function (
    int $heightCm,
    string $sensitivity,
    ?string $context,
    int $expectedLimit,
    string $expectedStatus,
    bool $expectedCautionContext,
): void {
    $result = (new ResolveCaffeineLimit)->handle($heightCm, $sensitivity, $context);

    expect($result->limitMg)->toBe($expectedLimit)
        ->and($result->status)->toBe($expectedStatus)
        ->and($result->hasCautionContext)->toBe($expectedCautionContext)
        ->and($result->reasons)->not->toBeEmpty();
})->with([
    'reference height low sensitivity' => [170, 'low', null, 400, 'height_adjusted_limit', false],
    'reference height normal sensitivity' => [170, 'normal', null, 300, 'height_adjusted_limit', false],
    'reference height high sensitivity' => [170, 'high', null, 200, 'height_adjusted_limit', false],
    'shorter height normal sensitivity' => [150, 'normal', null, 275, 'height_adjusted_limit', false],
    'taller height remains capped at adult reference' => [190, 'low', null, 400, 'height_adjusted_limit', false],
    'pregnancy context before sensitivity' => [170, 'normal', 'Trying to conceive', 150, 'context_limited', true],
]);
