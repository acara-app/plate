<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\VisualDataset;
use App\Enums\HealthAggregationFunction;
use App\Models\HealthDailyAggregate;
use App\Services\TurnDataRegistry;
use App\Services\VisualCatalog;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

final readonly class RegisterDailyHealthDatasets
{
    public function __construct(
        private TurnDataRegistry $datasets,
        private VisualCatalog $catalog,
    ) {}

    /**
     * @param  Collection<int, HealthDailyAggregate>  $aggregates
     */
    public function handle(Collection $aggregates, CarbonInterface $from, CarbonInterface $to): void
    {
        if (! $this->datasets->isCollecting()) {
            return;
        }

        $days = array_values(array_map(
            fn (CarbonInterface $day): string => $day->toDateString(),
            CarbonPeriod::create($from->toDateString(), $to->toDateString())->toArray(),
        ));

        if ($days === [] || count($days) > $this->catalog->limits()['pointsPerSeries']) {
            return;
        }

        foreach ($aggregates->groupBy('type_identifier') as $type => $typeAggregates) {
            $dataset = $this->dataset($typeAggregates, $days);

            if ($dataset instanceof VisualDataset) {
                $this->datasets->register((string) $type, $dataset);
            }
        }
    }

    /**
     * @param  Collection<int, HealthDailyAggregate>  $aggregates
     * @param  list<string>  $days
     */
    private function dataset(Collection $aggregates, array $days): ?VisualDataset
    {
        $first = $aggregates->first();

        if (! $first instanceof HealthDailyAggregate) {
            return null;
        }

        $descriptor = $first->descriptor();
        $unit = $first->unit ?? $descriptor->displayUnit;
        $values = [];

        foreach ($aggregates as $aggregate) {
            $value = $aggregate->primaryValue();

            if ($value !== null) {
                $values[($aggregate->local_date ?? $aggregate->date)->toDateString()] = $this->number($value);
            }
        }

        if ($values === [] || ! $descriptor->isKnown() || $unit === '') {
            return null;
        }

        return new VisualDataset(
            label: $descriptor->label,
            measure: $this->measure($first->aggregation_function === null ? $descriptor->function : HealthAggregationFunction::tryFrom($first->aggregation_function)),
            unit: $unit,
            rows: array_map(fn (string $day): array => ['day' => $day, 'value' => $values[$day] ?? null], $days),
        );
    }

    private function number(float $value): int|float
    {
        $rounded = round($value, 1);

        return $rounded === floor($rounded) ? (int) $rounded : $rounded;
    }

    private function measure(?HealthAggregationFunction $function): string
    {
        return match ($function) {
            HealthAggregationFunction::Sum => 'daily total',
            HealthAggregationFunction::Avg, HealthAggregationFunction::WeightedAvg => 'daily average',
            HealthAggregationFunction::Min => 'daily lowest',
            HealthAggregationFunction::Max => 'daily highest',
            HealthAggregationFunction::Last => 'last reading of each day',
            HealthAggregationFunction::Count => 'daily count',
            HealthAggregationFunction::None, null => 'daily value',
        };
    }
}
