<?php

declare(strict_types=1);

namespace App\Data\Skills;

use Spatie\LaravelData\Data;

/**
 * @codeCoverageIgnore
 */
final class SkillContent extends Data
{
    /**
     * @param  array<int, string>|null  $allowedTools
     */
    public function __construct(
        public string $name,
        public string $description,
        public string $content,
        public string $path,
        public ?array $allowedTools = null,
    ) {}
}
