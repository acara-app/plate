<?php

declare(strict_types=1);

namespace App\Enums;

enum DataSensitivity: string
{
    case General = 'general';
    case Personal = 'personal';
    case Sensitive = 'sensitive';

    public static function max(self ...$values): self
    {
        $max = self::General;

        foreach ($values as $value) {
            if ($value->isAtLeast($max)) {
                $max = $value;
            }
        }

        return $max;
    }

    public function rank(): int
    {
        return match ($this) {
            self::General => 0,
            self::Personal => 1,
            self::Sensitive => 2,
        };
    }

    public function isAtLeast(self $other): bool
    {
        return $this->rank() >= $other->rank();
    }
}
