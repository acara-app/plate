<?php

declare(strict_types=1);

namespace App\DataObjects\MealPlanContext;

use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\CamelCaseMapper;

#[MapOutputName(CamelCaseMapper::class)]
final class MealPlanContextData extends Data
{
    /**
     * @param  DataCollection<int, DietaryPreferenceData>  $dietaryPreferences
     * @param  DataCollection<int, HealthConditionData>  $healthConditions
     */
    public function __construct(
        // Physical metrics
        public ?int $age,
        public ?float $height,
        public ?float $weight,
        public ?string $sex,
        public ?float $bmi,
        public ?float $bmr,
        public ?float $tdee,

        // Goals
        public ?string $goal,
        public ?float $targetWeight,
        public ?string $additionalGoals,

        // Lifestyle
        public ?LifestyleData $lifestyle,

        // Dietary preferences
        #[DataCollectionOf(DietaryPreferenceData::class)]
        public DataCollection $dietaryPreferences,

        // Health conditions
        #[DataCollectionOf(HealthConditionData::class)]
        public DataCollection $healthConditions,

        // Calculated values
        public ?float $dailyCalorieTarget,
        public MacronutrientRatiosData $macronutrientRatios,

        // Glucose data analysis
        public ?GlucoseAnalysisData $glucoseAnalysis,
    ) {}
}
