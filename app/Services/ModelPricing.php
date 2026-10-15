<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class ModelPricing
{
    /**
     * @return array{input: float, output: float, reasoning: float, cache_read: float, cache_write?: float}
     */
    public static function forModel(string $model, ?string $provider = null): array
    {
        $models = [];
        foreach (config()->array('ai.model_catalog') as $name => $definition) {
            if (! is_string($name)) {
                continue;
            }

            if (! is_array($definition)) {
                continue;
            }

            if (! is_array($definition['pricing'] ?? null)) {
                continue;
            }

            if ($provider !== null && ($definition['provider'] ?? null) !== $provider) {
                continue;
            }

            $models[$name] = $definition;
        }

        $match = $models[$model] ?? self::matchByBase($model, $models);

        if ($match !== null) {
            return self::currentRates($match);
        }

        return self::rates(config()->array('ai.default_model_pricing'));
    }

    /**
     * @param  array<array-key, mixed>  $definition
     * @return array{input: float, output: float, reasoning: float, cache_read: float, cache_write: float}
     */
    private static function currentRates(array $definition): array
    {
        $pricing = is_array($definition['pricing'] ?? null) ? $definition['pricing'] : [];
        $changes = $definition['pricing_changes'] ?? [];
        if (is_array($changes)) {
            ksort($changes);
            foreach ($changes as $date => $changedPricing) {
                if (is_string($date) && now()->toDateString() >= $date && is_array($changedPricing)) {
                    $pricing = $changedPricing;
                }
            }
        }

        return self::rates($pricing);
    }

    /**
     * @param  array<array-key, mixed>  $pricing
     * @return array{input: float, output: float, reasoning: float, cache_read: float, cache_write: float}
     */
    private static function rates(array $pricing): array
    {
        $input = self::rate($pricing['input'] ?? null);

        return [
            'input' => $input,
            'output' => self::rate($pricing['output'] ?? null),
            'reasoning' => self::rate($pricing['reasoning'] ?? null),
            'cache_read' => self::rate($pricing['cache_read'] ?? null),
            'cache_write' => self::rate($pricing['cache_write'] ?? $input * 1.25),
        ];
    }

    private static function rate(mixed $rate): float
    {
        throw_if((! is_int($rate) && ! is_float($rate)) || $rate < 0, InvalidArgumentException::class, 'Model prices must be non-negative numbers.');

        return (float) $rate;
    }

    /**
     * @param  array<string, array<array-key, mixed>>  $models
     * @return array<array-key, mixed>|null
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
