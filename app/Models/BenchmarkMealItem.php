<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\NutrientValues;
use App\Enums\Benchmark\TruthSource;
use Database\Factories\BenchmarkMealItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $benchmark_meal_id
 * @property int $position
 * @property string $name
 * @property bool $visible
 * @property float $weight_g
 * @property float $kcal_per_100g
 * @property float $carbs_per_100g
 * @property float $protein_per_100g
 * @property float $fat_per_100g
 * @property TruthSource $truth_source
 * @property string|null $truth_ref
 * @property string|null $notes
 */
final class BenchmarkMealItem extends Model
{
    /** @use HasFactory<BenchmarkMealItemFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<BenchmarkMeal, $this>
     */
    public function meal(): BelongsTo
    {
        return $this->belongsTo(BenchmarkMeal::class, 'benchmark_meal_id');
    }

    public function asServed(): NutrientValues
    {
        $factor = $this->weight_g / 100;

        return new NutrientValues(
            calories: round($this->kcal_per_100g * $factor, 1),
            protein: round($this->protein_per_100g * $factor, 1),
            carbs: round($this->carbs_per_100g * $factor, 1),
            fat: round($this->fat_per_100g * $factor, 1),
        );
    }

    public function casts(): array
    {
        return [
            'visible' => 'boolean',
            'weight_g' => 'float',
            'kcal_per_100g' => 'float',
            'carbs_per_100g' => 'float',
            'protein_per_100g' => 'float',
            'fat_per_100g' => 'float',
            'truth_source' => TruthSource::class,
        ];
    }
}
