<?php

declare(strict_types=1);

namespace App\Ai\Attributes;

use App\Enums\DataSensitivity;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AiToolSensitivity
{
    public function __construct(
        public DataSensitivity $sensitivity,
    ) {}
}
