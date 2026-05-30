<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Support\Uri;

final class StaticUrl
{
    public static function mealPlanUrl(): string
    {
        return (string) Uri::of(config()->string('app.url'))
            ->withPath(route('meal-plans.index', [], false));
    }

    public static function checkoutUrl(): string
    {
        return (string) Uri::of(config()->string('app.url'))
            ->withPath(route('checkout.subscription', [], false));
    }
}
