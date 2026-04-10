<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HealthAggregateCategory;
use App\Enums\HealthAggregationFunction;
use App\Services\HealthMetricRegistry;
use App\ValueObjects\HealthMetricDescriptorData;
use Carbon\CarbonInterface;
use Database\Factories\HealthDailyAggregateFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property CarbonInterface $date
 * @property CarbonInterface|null $local_date
 * @property string|null $timezone
 * @property string $type_identifier
 * @property float|null $value_sum
 * @property float|null $value_sum_canonical
 * @property float|null $value_avg
 * @property float|null $value_min
 * @property float|null $value_max
 * @property float|null $value_last
 * @property int $value_count
 * @property string|null $source_primary
 * @property string|null $unit
 * @property string|null $canonical_unit
 * @property string|null $aggregation_function
 * @property int $aggregation_version
 * @property array<string, mixed>|null $metadata
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read User $user
 */
final class HealthDailyAggregate extends Model
{
    /** @use HasFactory<HealthDailyAggregateFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'date' => 'date',
            'local_date' => 'date',
            'value_sum' => 'float',
            'value_sum_canonical' => 'float',
            'value_avg' => 'float',
            'value_min' => 'float',
            'value_max' => 'float',
            'value_last' => 'float',
            'value_count' => 'integer',
            'aggregation_version' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function descriptor(): HealthMetricDescriptorData
    {
        /** @var HealthMetricRegistry $registry */
        $registry = resolve(HealthMetricRegistry::class);

        return $registry->descriptorOrUnknown($this->type_identifier);
    }

    public function category(): HealthAggregateCategory
    {
        return $this->descriptor()->category;
    }

    public function primaryValue(): ?float
    {
        $fn = $this->aggregation_function !== null
            ? HealthAggregationFunction::tryFrom($this->aggregation_function)
            : $this->descriptor()->function;

        return match ($fn) {
            HealthAggregationFunction::Sum => $this->value_sum,
            HealthAggregationFunction::Avg, HealthAggregationFunction::WeightedAvg => $this->value_avg,
            HealthAggregationFunction::Min => $this->value_min,
            HealthAggregationFunction::Max => $this->value_max,
            HealthAggregationFunction::Last => $this->value_last,
            HealthAggregationFunction::Count => (float) $this->value_count,
            HealthAggregationFunction::None, null => null,
        };
    }

    #[Scope]
    protected function forDate(Builder $query, CarbonInterface $date): void
    {
        $query->where('local_date', $date->toDateString());
    }

    #[Scope]
    protected function forDateRange(Builder $query, CarbonInterface $from, CarbonInterface $to): void
    {
        $query->whereBetween('local_date', [$from->toDateString(), $to->toDateString()]);
    }

    #[Scope]
    protected function ofType(Builder $query, HealthMetricDescriptorData|string $type): void
    {
        $query->where('type_identifier', $type instanceof HealthMetricDescriptorData ? $type->identifier : $type);
    }
}
