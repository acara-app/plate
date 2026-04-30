<?php

declare(strict_types=1);

use App\Actions\ResolveCaffeineLimit;
use App\Data\CaffeineLimitData;

covers(ResolveCaffeineLimit::class);

it('resolves deterministic caffeine limits', function (
    array $profile,
    int $expectedLimit,
    string $expectedStatus = 'weight_adjusted_limit',
    bool $expectedCautionContext = false,
): void {
    $result = resolveCaffeineLimit($profile);

    expect($result->limitMg)->toBe($expectedLimit)
        ->and($result->status)->toBe($expectedStatus)
        ->and($result->hasCautionContext)->toBe($expectedCautionContext)
        ->and($result->reasons)->not->toBeEmpty()
        ->and($result->formulaUsed)->toBe('efsa_weight_based');
})->with([
    'reference weight low sensitivity' => [['sensitivity' => 'low'], 200],
    'reference weight normal sensitivity' => [[], 150],
    'reference weight high sensitivity' => [['sensitivity' => 'high'], 100],
    'shorter height normal sensitivity' => [['heightCm' => 150, 'weightKg' => 60, 'sex' => 'female'], 125],
    'taller weight low sensitivity' => [['heightCm' => 190, 'weightKg' => 90, 'sensitivity' => 'low'], 250],
    'high weight saturates absolute cap' => [['weightKg' => 200, 'sensitivity' => 'low'], 500],
    'mid-high weight passes former 400 cap' => [['weightKg' => 150, 'sex' => 'female', 'sensitivity' => 'low'], 450],
    'senior age uses 0.80 modifier' => [['age' => 65], 125],
    'female 50+ uses 0.85 modifier' => [['age' => 50, 'sex' => 'female', 'sensitivity' => 'low'], 175],
    'pregnancy context before sensitivity' => [['sex' => 'female', 'context' => 'Trying to conceive'], 150, 'context_limited', true],
]);

it('accepts explicit pregnancy condition without text scanning', function (): void {
    $result = resolveCaffeineLimit(['sex' => 'female', 'sensitivity' => 'low'], ['pregnancy']);

    expect($result->limitMg)->toBe(200)
        ->and($result->hasCautionContext)->toBeTrue()
        ->and($result->contextLabel)->toContain('Pregnancy')
        ->and($result->conditions)->toBe(['pregnancy']);
});

it('detects health conditions from free-text context', function (string $context, array $expectedConditions): void {
    expect(resolveCaffeineLimit(['context' => $context])->conditions)->toBe($expectedConditions);
})->with([
    'pregnancy detected' => ['I am pregnant', ['pregnancy']],
    'breastfeeding detected' => ['I am breastfeeding', ['breastfeeding']],
    'negated pregnancy' => ['I am not pregnant and no pregnancy here', []],
    'negated breastfeeding' => ['not breastfeeding, not breast feeding, not nursing', []],
    'heart condition' => ['I have a heart condition and cardiac issues', ['heart_condition']],
    'anxiety' => ['I feel anxious and have panic attack', ['anxiety']],
    'gerd' => ['I have gerd and acid reflux', ['gerd']],
    'insomnia' => ['I have insomnia and trouble sleeping', ['insomnia']],
    'medication' => ['I take medication and beta blocker', ['medication']],
]);

/**
 * @param  array{heightCm?: int, weightKg?: int|float, age?: int, sex?: string, sensitivity?: string, context?: string|null}  $profile
 * @param  array<int, string>  $conditions
 */
function resolveCaffeineLimit(array $profile = [], array $conditions = []): CaffeineLimitData
{
    return (new ResolveCaffeineLimit)->handle(
        heightCm: $profile['heightCm'] ?? 170,
        weightKg: (float) ($profile['weightKg'] ?? 70),
        age: $profile['age'] ?? 30,
        sex: $profile['sex'] ?? 'male',
        sensitivity: $profile['sensitivity'] ?? 'normal',
        context: $profile['context'] ?? null,
        conditions: $conditions,
    );
}
