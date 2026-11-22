<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Str;

final readonly class VisualDataset
{
    public const string UTC_DAYS = 'utc';

    /**
     * @param  list<array{day: string, value: int|float|null}>  $rows  one row per day, oldest first, with null for a day without a reading
     * @param  array{min: float, max: float}|null  $valueRange  the values this unit can hold, when a goal or range in another unit would be easy to mistake for it
     */
    public function __construct(
        public string $label,
        public string $measure,
        public string $unit,
        public array $rows,
        public string $dayBoundary = self::UTC_DAYS,
        public ?array $valueRange = null,
    ) {}

    public function describe(string $handle): string
    {
        $days = count($this->rows);
        $missing = count(array_filter($this->rows, fn (array $row): bool => $row['value'] === null));
        $from = $this->rows[0]['day'] ?? '';
        $to = $this->rows[$days - 1]['day'] ?? '';

        return "- {$handle}: {$this->label}, {$this->measure} in {$this->unit}, {$from} to {$to}, {$days} ".Str::plural('day', $days).", {$missing} without a reading";
    }
}
