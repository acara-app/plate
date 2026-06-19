<?php

declare(strict_types=1);

namespace App\Services;

final class ModelPricing
{
    /**
     * @return array{input: float, output: float, reasoning: float, cache_read: float}
     */
    public static function forModel(string $model): array
    {
        /** @var array<string, array{input: float, output: float, reasoning: float, cache_read: float}> $models */
        $models = config()->array('plate.model_pricing.models', []);

        $match = $models[$model] ?? self::matchByBase($model, $models);

        if ($match !== null) {
            return $match;
        }

        /** @var array{input: float, output: float, reasoning: float, cache_read: float} $default */
        $default = config()->array('plate.model_pricing.default', [
            'input' => 0.50,
            'output' => 2.00,
            'reasoning' => 0.0,
            'cache_read' => 0.25,
        ]);

        return $default;
    }

    /**
     * @param  array<string, array{input: float, output: float, reasoning: float, cache_read: float}>  $models
     * @return array{input: float, output: float, reasoning: float, cache_read: float}|null
     */
    private static function matchByBase(string $model, array $models): ?array
    {
        $bestKey = null;

        foreach (array_keys($models) as $key) {
            if (! str_starts_with($model, $key.'-')) {
                continue;
            }

            // @codeCoverageIgnoreStart
            if ($bestKey === null || mb_strlen($key) > mb_strlen($bestKey)) {
                $bestKey = $key;
            }
            // @codeCoverageIgnoreEnd
        }

        return $bestKey !== null ? $models[$bestKey] : null;
    }
}
