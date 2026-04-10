<?php

declare(strict_types=1);

namespace Tests\Unit\Utilities;

use App\Utilities\ConfigHelper;

covers(ConfigHelper::class);

it('returns integer for numeric string config value', function (): void {
    config(['test.numeric_string' => '42']);

    expect(ConfigHelper::int('test.numeric_string', 0))->toBe(42);
});

it('returns integer for integer config value', function (): void {
    config(['test.integer' => 100]);

    expect(ConfigHelper::int('test.integer', 0))->toBe(100);
});

it('returns default for non-numeric string config value', function (): void {
    config(['test.non_numeric' => 'abc']);

    expect(ConfigHelper::int('test.non_numeric', 99))->toBe(99);
});

it('returns default for missing config key', function (): void {
    expect(ConfigHelper::int('test.nonexistent.key', 50))->toBe(50);
});

it('returns default for array config value', function (): void {
    config(['test.array' => ['a', 'b']]);

    expect(ConfigHelper::int('test.array', 25))->toBe(25);
});

it('returns default for null config value', function (): void {
    config(['test.null' => null]);

    expect(ConfigHelper::int('test.null', 10))->toBe(10);
});
