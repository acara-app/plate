<?php

declare(strict_types=1);

use App\Enums\ContentType;

it('returns correct label for food type', function (): void {
    expect(ContentType::Food->label())->toBe('Food');
});

it('returns correct label for usda daily serving size type', function (): void {
    expect(ContentType::UsdaDailyServingSize->label())->toBe('USDA Daily Serving Size');
});

it('returns correct label for usda sugar limit type', function (): void {
    expect(ContentType::UsdaSugarLimit->label())->toBe('USDA Sugar Limit');
});
