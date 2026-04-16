<?php

declare(strict_types=1);

use App\Enums\MemoryCategory;
use App\Enums\MemoryType;

it('exposes all memory type values', function (): void {
    expect(MemoryType::values())->toBe([
        'fact',
        'preference',
        'goal',
        'event',
        'skill',
        'relationship',
        'habit',
        'context',
    ]);
});

it('exposes all memory category values', function (): void {
    expect(MemoryCategory::values())->toBe([
        'personal',
        'professional',
        'hobbies',
        'health',
        'relationships',
        'preferences',
        'goals',
    ]);
});
