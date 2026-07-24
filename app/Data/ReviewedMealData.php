<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\HealthEntryType;
use App\Models\AnalysisDraft;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

final class ReviewedMealData extends Data
{
    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function __construct(
        public array $items,
        public CarbonImmutable $measuredAt,
        public ?string $notes = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromValidated(array $data): self
    {
        $items = [];

        foreach (is_array($data['items'] ?? null) ? $data['items'] : [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $normalized = [];

            foreach ($item as $key => $value) {
                $normalized[(string) $key] = $value;
            }

            $items[] = $normalized;
        }

        return new self(
            items: $items,
            measuredAt: Date::parse(is_string($data['measured_at'] ?? null) ? $data['measured_at'] : 'now')->toImmutable(),
            notes: is_string($data['notes'] ?? null) && mb_trim($data['notes']) !== '' ? $data['notes'] : null,
        );
    }

    public function toHealthLogData(AnalysisDraft $draft): HealthLogData
    {
        return new HealthLogData(
            isHealthData: true,
            logType: HealthEntryType::Food,
            carbsGrams: $this->totalOf('carbs'),
            proteinGrams: $this->totalOf('protein'),
            fatGrams: $this->totalOf('fat'),
            calories: (int) round($this->totalOf('calories')),
            measuredAt: $this->measuredAt,
            notes: $this->mealLabel(),
            foodItems: $this->normalizedItems(),
            confidence: is_int($draft->payload['confidence'] ?? null) ? $draft->payload['confidence'] : null,
            analyzerVersion: is_string($draft->payload['analyzer_version'] ?? null) ? $draft->payload['analyzer_version'] : null,
            foodSource: $draft->source->value,
            draftReference: mb_substr($draft->token_hash, 0, 12),
        );
    }

    public function totalOf(string $field): float
    {
        $total = 0.0;

        foreach ($this->items as $item) {
            $total += is_numeric($item[$field] ?? null) ? (float) $item[$field] : 0.0;
        }

        return round($total, 1);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizedItems(): array
    {
        return array_map(fn (array $item): array => [
            'name' => is_string($item['name'] ?? null) ? $item['name'] : '',
            'portion' => is_string($item['portion'] ?? null) ? $item['portion'] : null,
            'calories' => is_numeric($item['calories'] ?? null) ? round((float) $item['calories'], 1) : 0.0,
            'protein' => is_numeric($item['protein'] ?? null) ? round((float) $item['protein'], 1) : 0.0,
            'carbs' => is_numeric($item['carbs'] ?? null) ? round((float) $item['carbs'], 1) : 0.0,
            'fat' => is_numeric($item['fat'] ?? null) ? round((float) $item['fat'], 1) : 0.0,
            'provenance' => in_array($item['provenance'] ?? null, ['model', 'reference', 'user'], true) ? $item['provenance'] : 'user',
        ], $this->items);
    }

    private function mealLabel(): string
    {
        if ($this->notes !== null) {
            return $this->notes;
        }

        $names = array_filter(array_map(
            fn (array $item): string => is_string($item['name'] ?? null) ? mb_trim($item['name']) : '',
            $this->items,
        ), fn (string $name): bool => $name !== '');

        return Str::limit(implode(', ', $names), 500, '');
    }
}
