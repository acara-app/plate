<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class PreviousDayContext extends Data
{
    /**
     * @param  array<int, array<string>>  $previousMealNames  Array keyed by day number containing meal names
     */
    public function __construct(
        public array $previousMealNames = [],
    ) {}

    /**
     * Add meals from a day to the context.
     *
     * @param  array<string>  $mealNames
     */
    public function addDayMeals(int $dayNumber, array $mealNames): self
    {
        $this->previousMealNames[$dayNumber] = $mealNames;

        return $this;
    }

    /**
     * Get a formatted string of previous meals for the prompt.
     */
    public function toPromptText(): string
    {
        if ($this->previousMealNames === []) {
            return '';
        }

        $lines = ["## Previous Days' Meals (Avoid Repeating)", ''];

        foreach ($this->previousMealNames as $dayNumber => $mealNames) {
            $lines[] = sprintf('**Day %d**: ', $dayNumber).implode(', ', $mealNames);
        }

        $lines[] = '';
        $lines[] = '**Important**: Create different meals than those listed above to ensure variety throughout the meal plan.';

        return implode("\n", $lines);
    }
}
