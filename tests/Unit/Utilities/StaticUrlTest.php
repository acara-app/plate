<?php

declare(strict_types=1);

use App\Utilities\StaticUrl;

covers(StaticUrl::class);

it('returns the absolute meal plans url using app url', function (): void {
    $url = StaticUrl::mealPlanUrl();

    expect($url)->toStartWith('http')
        ->and($url)->toContain('/meal-plans');
});

it('returns the absolute checkout url from the configured app url, not the request host', function (): void {
    config(['app.url' => 'https://acara.example']);

    expect(StaticUrl::checkoutUrl())->toBe('https://acara.example/checkout/subscription');
});
