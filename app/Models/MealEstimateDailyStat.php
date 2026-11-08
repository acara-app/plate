<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnalysisDraftSource;
use Carbon\CarbonInterface;
use Database\Factories\MealEstimateDailyStatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $date
 * @property string $model_version
 * @property AnalysisDraftSource $source
 * @property int $meals
 * @property int $corrected
 * @property array<int, int> $kcal_changes
 * @property array<int, int> $kcal_change_percents
 * @property array<int, int> $carbs_changes
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class MealEstimateDailyStat extends Model
{
    /** @use HasFactory<MealEstimateDailyStatFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'source' => AnalysisDraftSource::class,
            'meals' => 'integer',
            'corrected' => 'integer',
            'kcal_changes' => 'array',
            'kcal_change_percents' => 'array',
            'carbs_changes' => 'array',
        ];
    }
}
