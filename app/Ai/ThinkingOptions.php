<?php

declare(strict_types=1);

namespace App\Ai;

use App\Enums\ModelName;
use Laravel\Ai\Enums\Lab;

final class ThinkingOptions
{
    /**
     * @return array<string, mixed>
     */
    public static function forModel(ModelName $model, Lab|string $provider): array
    {
        if (! $model->requiresThinkingMode()) {
            return [];
        }

        if ($provider !== Lab::Gemini && $provider !== Lab::Gemini->value) {
            return [];
        }

        $budget = $model->getThinkingBudget();

        if ($budget === null) {
            return [];
        }

        return [
            'thinkingConfig' => [
                'thinkingBudget' => $budget,
                'includeThoughts' => true,
            ],
        ];
    }
}
