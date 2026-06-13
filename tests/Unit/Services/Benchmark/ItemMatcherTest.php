<?php

declare(strict_types=1);

use App\Services\Benchmark\ItemMatcher;

beforeEach(function (): void {
    $this->matcher = new ItemMatcher;
});

it('matches exact and subset names one-to-one', function (): void {
    $score = $this->matcher->score(
        ['rice, white, cooked', 'chicken'],
        ['rice, white, cooked', 'chicken breast, grilled'],
    );

    expect($score->recall)->toBe(1.0)
        ->and($score->precision)->toBe(1.0);
});

it('never assigns one predicted item to two truth items', function (): void {
    $score = $this->matcher->score(
        ['rice'],
        ['rice, white', 'rice, brown'],
    );

    expect($score->recall)->toBe(0.5)
        ->and($score->precision)->toBe(1.0);
});

it('counts unrelated predictions as misses on both sides', function (): void {
    $score = $this->matcher->score(
        ['garden salad', 'rice, white, cooked'],
        ['chicken breast, grilled', 'rice, white, cooked'],
    );

    expect($score->recall)->toBe(0.5)
        ->and($score->precision)->toBe(0.5);
});

it('is not applicable when the meal has no visible truth items', function (): void {
    $score = $this->matcher->score(['lasagna'], []);

    expect($score->recall)->toBeNull()
        ->and($score->precision)->toBeNull();
});

it('scores zero recall with undefined precision when nothing was predicted', function (): void {
    $score = $this->matcher->score([], ['rice, white, cooked']);

    expect($score->recall)->toBe(0.0)
        ->and($score->precision)->toBeNull();
});

it('matches localized-name fallbacks through normalization', function (): void {
    $score = $this->matcher->score(
        ['Chicken Breast (Grilled)'],
        ['chicken breast, grilled'],
    );

    expect($score->recall)->toBe(1.0)
        ->and($score->precision)->toBe(1.0);
});
