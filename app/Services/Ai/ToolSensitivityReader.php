<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Attributes\AiToolSensitivity;
use App\Enums\DataSensitivity;
use ReflectionClass;

final class ToolSensitivityReader
{
    /**
     * @param  class-string|object  $tool
     */
    public function forTool(string|object $tool): DataSensitivity
    {
        $reflection = new ReflectionClass($tool);
        $attributes = $reflection->getAttributes(AiToolSensitivity::class);

        if ($attributes === []) {
            return DataSensitivity::Sensitive;
        }

        return $attributes[0]->newInstance()->sensitivity;
    }

    /**
     * @param  array<int, class-string|object>  $tools
     */
    public function maxSensitivity(array $tools): DataSensitivity
    {
        if ($tools === []) {
            return DataSensitivity::General;
        }

        return DataSensitivity::max(
            ...array_map($this->forTool(...), $tools),
        );
    }
}
