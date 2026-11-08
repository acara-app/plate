<?php

declare(strict_types=1);

namespace App\Services;

final class ModelPricing
{
    /** @var array<string, array<string, array{input: float, output: float, reasoning: float, cache_read: float}>> */
    private const array PRICES = [
        'gpt-6-sol' => [
            '2000-01-01' => ['input' => 2.0, 'output' => 10.0, 'reasoning' => 10.0, 'cache_read' => 0.2],
        ],
        'gpt-6.1-sol' => [
            '2000-01-01' => ['input' => 2.0, 'output' => 10.0, 'reasoning' => 10.0, 'cache_read' => 0.10],
        ],
        'gpt-6.1-terra' => [
            '2000-01-01' => ['input' => 1.0, 'output' => 5.0, 'reasoning' => 5.0, 'cache_read' => 0.05],
        ],
        'gpt-6-luna' => [
            '2000-01-01' => ['input' => 0.10, 'output' => 0.50, 'reasoning' => 0.50, 'cache_read' => 0.01],
        ],
        'gemini-3.6-flash' => [
            '2000-01-01' => ['input' => 0.75, 'output' => 3.75, 'reasoning' => 3.75, 'cache_read' => 0.075],
            '2027-01-01' => ['input' => 1.50, 'output' => 7.50, 'reasoning' => 7.50, 'cache_read' => 0.15],
        ],
        'gemini-3.8-flash' => [
            '2000-01-01' => ['input' => 0.75, 'output' => 3.75, 'reasoning' => 3.75, 'cache_read' => 0.075],
            '2027-01-01' => ['input' => 1.50, 'output' => 7.50, 'reasoning' => 7.50, 'cache_read' => 0.15],
        ],
    ];

    private const string FALLBACK = 'gpt-6-sol';

    /**
     * @return array{input: float, output: float, reasoning: float, cache_read: float, cache_write: float}
     */
    public static function forModel(string $model): array
    {
        $current = null;

        foreach (self::PRICES[self::baseModel($model)] as $date => $pricing) {
            if (now()->toDateString() >= $date) {
                $current = $pricing;
            }
        }

        $current ??= self::PRICES[self::FALLBACK]['2000-01-01'];

        return [
            'input' => $current['input'],
            'output' => $current['output'],
            'reasoning' => $current['reasoning'],
            'cache_read' => $current['cache_read'],
            'cache_write' => $current['input'] * 1.25,
        ];
    }

    private static function baseModel(string $model): string
    {
        if (array_key_exists($model, self::PRICES)) {
            return $model;
        }

        foreach (array_keys(self::PRICES) as $base) {
            if (str_starts_with($model, $base.'-')) {
                return $base;
            }
        }

        return self::FALLBACK;
    }
}
