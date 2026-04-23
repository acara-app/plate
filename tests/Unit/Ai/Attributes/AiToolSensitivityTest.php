<?php

declare(strict_types=1);

use App\Ai\Attributes\AiToolSensitivity;
use App\Enums\DataSensitivity;

covers(AiToolSensitivity::class);

it('carries a DataSensitivity value', function (): void {
    $attribute = new AiToolSensitivity(DataSensitivity::Sensitive);

    expect($attribute->sensitivity)->toBe(DataSensitivity::Sensitive);
});

it('targets classes only', function (): void {
    $reflection = new ReflectionClass(AiToolSensitivity::class);
    $attributes = $reflection->getAttributes(Attribute::class);

    expect($attributes)->not->toBeEmpty();

    /** @var Attribute $attr */
    $attr = $attributes[0]->newInstance();

    expect($attr->flags & Attribute::TARGET_CLASS)->toBe(Attribute::TARGET_CLASS);
});
