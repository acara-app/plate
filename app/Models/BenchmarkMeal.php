<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\NutrientValues;
use App\Enums\Benchmark\CameraAngle;
use App\Enums\Benchmark\DishType;
use App\Enums\Benchmark\Lighting;
use App\Enums\Benchmark\Tranche;
use App\Enums\Benchmark\TruthScope;
use App\Enums\Benchmark\TruthSource;
use Database\Factories\BenchmarkMealFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property Tranche $tranche
 * @property \Carbon\CarbonInterface $collected_on
 * @property string $cuisine
 * @property DishType $dish_type
 * @property Lighting $lighting
 * @property CameraAngle $angle
 * @property TruthScope $truth_scope
 * @property float $total_weight_g
 * @property float|null $total_kcal
 * @property float|null $total_carbs_g
 * @property float|null $total_protein_g
 * @property float|null $total_fat_g
 * @property TruthSource|null $truth_source
 * @property string|null $truth_ref
 * @property string $photo_disk
 * @property string $photo_path
 * @property string|null $notes
 */
final class BenchmarkMeal extends Model
{
    /** @use HasFactory<BenchmarkMealFactory> */
    use HasFactory;

    public const string PHOTO_DIRECTORY = 'benchmark/golden-plates';

    protected $guarded = [];

    public static function nextCode(): string
    {
        $last = self::query()->max('code');

        $next = is_string($last) ? (int) mb_substr($last, 1) + 1 : 1;

        return sprintf('m%04d', $next);
    }

    /**
     * @return HasMany<BenchmarkMealItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(BenchmarkMealItem::class)->orderBy('position');
    }

    public function truthTotals(): NutrientValues
    {
        if ($this->truth_scope === TruthScope::MealOnly) {
            return new NutrientValues(
                calories: (float) $this->total_kcal,
                protein: (float) $this->total_protein_g,
                carbs: (float) $this->total_carbs_g,
                fat: (float) $this->total_fat_g,
            );
        }

        $served = $this->items->map(fn (BenchmarkMealItem $item): NutrientValues => $item->asServed());

        return new NutrientValues(
            calories: round($served->sum(fn (NutrientValues $values): float => $values->calories), 1),
            protein: round($served->sum(fn (NutrientValues $values): float => $values->protein), 1),
            carbs: round($served->sum(fn (NutrientValues $values): float => $values->carbs), 1),
            fat: round($served->sum(fn (NutrientValues $values): float => $values->fat), 1),
        );
    }

    public function casts(): array
    {
        return [
            'tranche' => Tranche::class,
            'collected_on' => 'date',
            'dish_type' => DishType::class,
            'lighting' => Lighting::class,
            'angle' => CameraAngle::class,
            'truth_scope' => TruthScope::class,
            'total_weight_g' => 'float',
            'total_kcal' => 'float',
            'total_carbs_g' => 'float',
            'total_protein_g' => 'float',
            'total_fat_g' => 'float',
            'truth_source' => TruthSource::class,
        ];
    }
}
