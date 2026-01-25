<?php

declare(strict_types=1);

namespace App\DataObjects\MealPlanContext;

use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use App\Enums\DietType;
use App\Enums\Sex;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(CamelCaseMapper::class)]
final class MealPlanContextData extends Data
{
    /**
     * @param  array<DietaryPreferenceData>  $dietaryPreferences
     * @param  array<HealthConditionData>  $healthConditions
     * @param  array<MedicationData>  $medications
     */
    public function __construct(
        // Physical metrics
        public ?int $age,
        public ?float $height,
        public ?float $weight,
        public ?Sex $sex,
        public ?float $bmi,
        public ?float $bmr,
        public ?float $tdee,

        // Goals
        public ?string $goal,
        public ?float $targetWeight,
        public ?string $additionalGoals,

        // Dietary preferences
        public array $dietaryPreferences,

        // Health conditions
        public array $healthConditions,

        // Medications
        public array $medications,

        // Calculated values
        public ?float $dailyCalorieTarget,
        public MacronutrientRatiosData $macronutrientRatios,

        // Diet type information
        public DietType $dietType,
        public string $dietTypeLabel,
        public string $dietTypeFocus,

        // Glucose data analysis
        public ?GlucoseAnalysisData $glucoseAnalysis,
    ) {}
}
