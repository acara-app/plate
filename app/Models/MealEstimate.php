<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnalysisDraftSource;
use App\Enums\EstimateValidationStatus;
use Carbon\CarbonInterface;
use Database\Factories\MealEstimateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property-read int $id
 * @property int $user_id
 * @property string $health_group_id
 * @property AnalysisDraftSource $source
 * @property string|null $model_version
 * @property array<string, mixed> $original_prediction
 * @property array<string, mixed>|null $corrected_value
 * @property EstimateValidationStatus $validation_status
 * @property CarbonInterface|null $corrected_at
 * @property-read User $user
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class MealEstimate extends Model
{
    /** @use HasFactory<MealEstimateFactory> */
    use HasFactory;

    use MassPrunable;

    public const int RETENTION_MONTHS = 12;

    protected $guarded = [];

    public static function deleteForGroup(int $userId, string $groupId): void
    {
        self::query()
            ->where('user_id', $userId)
            ->where('health_group_id', $groupId)
            ->delete();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()
            ->where('created_at', '<', now()->subMonths(self::RETENTION_MONTHS))
            ->orWhereNotExists(fn (QueryBuilder $meal): QueryBuilder => $meal
                ->selectRaw('1')
                ->from('health_sync_samples')
                ->whereColumn('health_sync_samples.user_id', 'meal_estimates.user_id')
                ->whereColumn('health_sync_samples.group_id', 'meal_estimates.health_group_id'));
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'user_id' => 'integer',
            'source' => AnalysisDraftSource::class,
            'original_prediction' => 'array',
            'corrected_value' => 'array',
            'validation_status' => EstimateValidationStatus::class,
            'corrected_at' => 'datetime',
        ];
    }
}
