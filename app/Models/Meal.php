<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MealType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $meal_plan_id
 * @property-read int $day_number
 * @property-read MealType $type
 * @property-read string $name
 * @property-read string|null $description
 * @property-read string|null $preparation_instructions
 * @property-read array<int, array{name: string, quantity: string, specificity?: string, barcode?: string}>|null $ingredients
 * @property-read string|null $portion_size
 * @property-read float $calories
 * @property-read float|null $protein_grams
 * @property-read float|null $carbs_grams
 * @property-read float|null $fat_grams
 * @property-read int|null $preparation_time_minutes
 * @property-read array<string, mixed>|null $metadata
 * @property-read array<string, mixed>|null $food_data_verification
 * @property-read int $sort_order
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read MealPlan $mealPlan
 */
final class Meal extends Model
{
    /** @use HasFactory<\Database\Factories\MealFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'meal_plan_id' => 'integer',
            'day_number' => 'integer',
            'type' => MealType::class,
            'name' => 'string',
            'description' => 'string',
            'preparation_instructions' => 'string',
            'ingredients' => 'array',
            'portion_size' => 'string',
            'calories' => 'decimal:2',
            'protein_grams' => 'decimal:2',
            'carbs_grams' => 'decimal:2',
            'fat_grams' => 'decimal:2',
            'preparation_time_minutes' => 'integer',
            'metadata' => 'array',
            'food_data_verification' => 'array',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MealPlan, $this>
     */
    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }

    /**
     * Calculate macronutrient percentages based on calories
     *
     * @return array{protein: float, carbs: float, fat: float}
     */
    public function macroPercentages(): array
    {
        if ($this->calories <= 0) {
            return ['protein' => 0, 'carbs' => 0, 'fat' => 0];
        }

        // 1g protein = 4 cal, 1g carbs = 4 cal, 1g fat = 9 cal
        $proteinCals = ($this->protein_grams ?? 0) * 4;
        $carbsCals = ($this->carbs_grams ?? 0) * 4;
        $fatCals = ($this->fat_grams ?? 0) * 9;

        $totalMacroCals = $proteinCals + $carbsCals + $fatCals;

        if ($totalMacroCals <= 0) {
            return ['protein' => 0, 'carbs' => 0, 'fat' => 0];
        }

        return [
            'protein' => round(($proteinCals / $totalMacroCals) * 100, 1),
            'carbs' => round(($carbsCals / $totalMacroCals) * 100, 1),
            'fat' => round(($fatCals / $totalMacroCals) * 100, 1),
        ];
    }

    /**
     * Check if this meal meets minimum protein requirements (e.g., 20g for main meals)
     */
    public function meetsProteinRequirement(float $minimumGrams = 20.0): bool
    {
        return ($this->protein_grams ?? 0) >= $minimumGrams;
    }

    /**
     * Get a human-readable day name (e.g., "Day 1" or "Monday" for weekly plans)
     */
    public function getDayName(): string
    {
        if ($this->mealPlan->type === \App\Enums\MealPlanType::Weekly && $this->day_number <= 7) {
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            return $days[$this->day_number - 1] ?? "Day {$this->day_number}";
        }

        return "Day {$this->day_number}";
    }
}
