<?php

declare(strict_types=1);

use App\Utilities\StaticUrl;

covers(StaticUrl::class);

it('returns the absolute meal plans url using app url', function (): void {
    $url = StaticUrl::mealPlanUrl();

    expect($url)->toStartWith('http')
        ->and($url)->toContain('/meal-plans');
});
