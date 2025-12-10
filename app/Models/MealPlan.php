<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MealPlanType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read MealPlanType $type
 * @property-read string|null $name
 * @property-read string|null $description
 * @property-read int $duration_days
 * @property-read float|null $target_daily_calories
 * @property-read array{protein: int, carbs: int, fat: int}|null $macronutrient_ratios
 * @property-read array<string, mixed>|null $metadata
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 * @property-read Collection<int, Meal> $meals
 * @property-read GroceryList|null $groceryList
 */
final class MealPlan extends Model
{
    /** @use HasFactory<\Database\Factories\MealPlanFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'type' => MealPlanType::class,
            'name' => 'string',
            'description' => 'string',
            'duration_days' => 'integer',
            'target_daily_calories' => 'decimal:2',
            'macronutrient_ratios' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Meal, $this>
     */
    public function meals(): HasMany
    {
        return $this->hasMany(Meal::class)->orderBy('day_number')->orderBy('sort_order');
    }

    /**
     * @return HasOne<GroceryList, $this>
     */
    public function groceryList(): HasOne
    {
        return $this->hasOne(GroceryList::class);
    }

    /**
     * Get meals for a specific day
     *
     * @return Collection<int, Meal>
     */
    public function mealsForDay(int $dayNumber): Collection
    {
        return $this->meals()->where('day_number', $dayNumber)->get();
    }

    /**
     * Calculate total calories for a specific day
     */
    public function totalCaloriesForDay(int $dayNumber): float
    {
        return (float) $this->meals()
            ->where('day_number', $dayNumber)
            ->sum('calories');
    }

    /**
     * Calculate average daily calories across all days
     */
    public function averageDailyCalories(): float
    {
        $totalCalories = $this->meals()->sum('calories');

        return $this->duration_days > 0 ? $totalCalories / $this->duration_days : 0;
    }

    /**
     * Calculate total macronutrients for a specific day
     *
     * @return array{protein: float, carbs: float, fat: float}
     */
    public function macrosForDay(int $dayNumber): array
    {
        $meals = $this->meals()->where('day_number', $dayNumber)->get();

        /** @var float $protein */
        $protein = $meals->sum('protein_grams');
        /** @var float $carbs */
        $carbs = $meals->sum('carbs_grams');
        /** @var float $fat */
        $fat = $meals->sum('fat_grams');

        return [
            'protein' => $protein,
            'carbs' => $carbs,
            'fat' => $fat,
        ];
    }
}
