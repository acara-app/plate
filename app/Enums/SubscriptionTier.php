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
            default => null,
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

    public function isPaid(): bool
    {
        return $this !== self::Free;
    }

    public function isAtLeast(self $other): bool
    {
        return $this->rank() >= $other->rank();
    }

    private function rank(): int
    {
        return match ($this) {
            self::Free => 0,
            self::Basic => 1,
            self::Plus => 2,
        };
    }
}
