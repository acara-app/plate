<?php

declare(strict_types=1);

use App\Models\BenchmarkMeal;

it('continues the meal code sequence from the highest existing code', function (): void {
    expect(BenchmarkMeal::nextCode())->toBe('m0001');

    BenchmarkMeal::factory()->create(['code' => 'm0007']);

    expect(BenchmarkMeal::nextCode())->toBe('m0008');
});
