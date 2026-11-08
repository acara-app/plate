<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\MealEstimateDailyFoodChangeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $date
 * @property string|null $ai_food
 * @property string|null $corrected_food
 * @property int $meals
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class MealEstimateDailyFoodChange extends Model
{
    /** @use HasFactory<MealEstimateDailyFoodChangeFactory> */
    use HasFactory;

    use MassPrunable;

    protected $guarded = [];

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where('date', '<', now()->subMonths(MealEstimate::RETENTION_MONTHS)->toDateString());
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'meals' => 'integer',
        ];
    }
}
