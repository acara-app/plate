<?php

declare(strict_types=1);

use App\Enums\ContentType;

it('returns correct label for food type', function (): void {
    expect(ContentType::Food->label())->toBe('Food');
});
