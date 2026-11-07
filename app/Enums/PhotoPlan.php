<?php

declare(strict_types=1);

namespace App\Enums;

enum PhotoPlan: string
{
    case Starter = 'starter';
    case SnapPro = 'snap-pro';

    public static function fromProductName(string $name): ?self
    {
        foreach (self::cases() as $plan) {
            if ($plan->productName() === $name) {
                return $plan;
            }
        }

        return null;
    }

    public function productName(): string
    {
        return match ($this) {
            self::Starter => 'Starter',
            self::SnapPro => 'Snap Pro',
        };
    }

    public function monthlyPriceCents(): int
    {
        return match ($this) {
            self::Starter => 299,
            self::SnapPro => 900,
        };
    }

    public function lookupKey(): string
    {
        return match ($this) {
            self::Starter => 'acara-plate-starter-monthly-v1',
            self::SnapPro => 'acara-plate-snap-pro-monthly-v1',
        };
    }
}
