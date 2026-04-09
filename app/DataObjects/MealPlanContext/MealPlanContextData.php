<?php

declare(strict_types=1);

namespace App\DataObjects\MealPlanContext;

use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use App\Enums\BloodType;
use App\Enums\DietType;
use App\Enums\Sex;
use Carbon\Carbon;
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
     * @param  array<ProfileAttributeData>  $dietaryPreferences
     * @param  array<ProfileAttributeData>  $healthConditions
     * @param  array<ProfileAttributeData>  $medications
     */
    public function __construct(
        public ?int $age,
        public ?Carbon $dateOfBirth,
        public ?float $height,
        public ?float $weight,
        public ?Sex $sex,
        public ?BloodType $bloodType,
        public ?float $bmi,
        public ?float $bmr,
        public ?float $tdee,

        public ?string $goal,
        public ?float $targetWeight,
        public ?string $additionalGoals,

        public array $dietaryPreferences,

        public array $healthConditions,

        public array $medications,

        public ?float $dailyCalorieTarget,
        public MacronutrientRatiosData $macronutrientRatios,

        public DietType $dietType,
        public string $dietTypeLabel,
        public string $dietTypeFocus,

        public ?GlucoseAnalysisData $glucoseAnalysis,
    ) {}
}
