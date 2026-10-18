<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\AddedFatOptionData;
use App\Data\FoodAnalysisData;
use App\Data\FoodItemData;
use App\Models\ReferenceFood;

final readonly class BuildAddedFatOptionsAction
{
    private const float TABLESPOON_GRAMS = 14.0;

    private const float SELECTED_TOLERANCE_GRAMS = 1.5;

    private const float MIN_FAT_ENERGY_SHARE = 0.8;

    /** @var array<string, float> */
    private const array TABLESPOONS = [
        'none' => 0.0,
        'half_tbsp' => 0.5,
        'one_tbsp' => 1.0,
        'two_tbsp' => 2.0,
    ];

    /** @var list<string> */
    private const array KEYWORDS = ['oil', 'dressing', 'vinaigrette', 'butter', 'ghee', 'mayo', 'mayonnaise', 'margarine', 'aioli', 'lard'];

    /**
     * @return array<int, list<AddedFatOptionData>>
     */
    public function handle(?FoodAnalysisData $analysis): array
    {
        if (! $analysis instanceof FoodAnalysisData) {
            return [];
        }

        $options = [];

        foreach ($analysis->items as $index => $item) {
            if ($this->isAddedFat($item)) {
                $options[$index] = $this->optionsFor($item);
            }
        }

        return $options;
    }

    private function isAddedFat(FoodItemData $item): bool
    {
        if ($item->grams === null || $item->grams <= 0.0 || $item->calories <= 0.0) {
            return false;
        }

        if ($item->fat * 9 / $item->calories < self::MIN_FAT_ENERGY_SHARE) {
            return false;
        }

        $tokens = explode(' ', ReferenceFood::normalizeName($item->name.' '.$item->matchName));

        return array_intersect($tokens, self::KEYWORDS) !== [];
    }

    /**
     * @return list<AddedFatOptionData>
     */
    private function optionsFor(FoodItemData $item): array
    {
        $itemGrams = (float) $item->grams;
        $options = [];

        foreach (self::TABLESPOONS as $key => $tablespoons) {
            $grams = $tablespoons * self::TABLESPOON_GRAMS;
            $factor = $grams / $itemGrams;

            $options[] = new AddedFatOptionData(
                key: $key,
                grams: $grams,
                calories: round($item->calories * $factor, 1),
                protein: round($item->protein * $factor, 1),
                carbs: round($item->carbs * $factor, 1),
                fat: round($item->fat * $factor, 1),
                selected: abs($grams - $itemGrams) <= self::SELECTED_TOLERANCE_GRAMS,
            );
        }

        return $options;
    }
}
