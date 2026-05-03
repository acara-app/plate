<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionTier: string
{
    case Free = 'free';
    case Basic = 'basic';
    case Plus = 'plus';

    public static function fromProductName(?string $name): ?self
    {
        return match (mb_strtolower((string) $name)) {
            'free' => self::Free,
            'basic' => self::Basic,
            'plus' => self::Plus,
            default => null, // @codeCoverageIgnore
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Basic => 'Basic',
            self::Plus => 'Plus',
        };
    }
}
