<?php

declare(strict_types=1);

use App\Actions\ResolveCaffeineLimit;

covers(ResolveCaffeineLimit::class);

it('resolves deterministic caffeine limits', function (
    int $heightCm,
    float $weightKg,
    int $age,
    string $sex,
    string $sensitivity,
    ?string $context,
    int $expectedLimit,
    string $expectedStatus,
    bool $expectedCautionContext,
): void {
    $result = (new ResolveCaffeineLimit)->handle($heightCm, $weightKg, $age, $sex, $sensitivity, $context);

    expect($result->limitMg)->toBe($expectedLimit)
        ->and($result->status)->toBe($expectedStatus)
        ->and($result->hasCautionContext)->toBe($expectedCautionContext)
        ->and($result->reasons)->not->toBeEmpty()
        ->and($result->formulaUsed)->toBe('efsa_weight_based');
})->with([
    'reference weight low sensitivity' => [170, 70, 30, 'male', 'low', null, 200, 'weight_adjusted_limit', false],
    'reference weight normal sensitivity' => [170, 70, 30, 'male', 'normal', null, 150, 'weight_adjusted_limit', false],
    'reference weight high sensitivity' => [170, 70, 30, 'male', 'high', null, 100, 'weight_adjusted_limit', false],
    'shorter height normal sensitivity' => [150, 60, 30, 'female', 'normal', null, 125, 'weight_adjusted_limit', false],
    'taller weight low sensitivity' => [190, 90, 30, 'male', 'low', null, 250, 'weight_adjusted_limit', false],
    'high weight saturates absolute cap' => [170, 200, 30, 'male', 'low', null, 500, 'weight_adjusted_limit', false],
    'mid-high weight passes former 400 cap' => [170, 150, 30, 'female', 'low', null, 450, 'weight_adjusted_limit', false],
    'senior age uses 0.80 modifier' => [170, 70, 65, 'male', 'normal', null, 125, 'weight_adjusted_limit', false],
    'female 50+ uses 0.85 modifier' => [170, 70, 50, 'female', 'low', null, 175, 'weight_adjusted_limit', false],
    'pregnancy context before sensitivity' => [170, 70, 30, 'female', 'normal', 'Trying to conceive', 150, 'context_limited', true],
]);

it('accepts explicit pregnancy condition without text scanning', function (): void {
    $result = (new ResolveCaffeineLimit)->handle(170, 70, 30, 'female', 'low', null, ['pregnancy']);

    expect($result->limitMg)->toBe(200)
        ->and($result->hasCautionContext)->toBeTrue()
        ->and($result->contextLabel)->toContain('Pregnancy')
        ->and($result->conditions)->toBe(['pregnancy']);
});
