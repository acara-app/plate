<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Str;

final readonly class VisualDataset
{
    public const string UTC_DAYS = 'utc';

    /**
     * @param  list<array{day: string, value: int|float|null}>  $rows  one row per day, oldest first, with null for a day without a reading
     */
    public function __construct(
        public string $label,
        public string $measure,
        public string $unit,
        public array $rows,
        public string $dayBoundary = self::UTC_DAYS,
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
