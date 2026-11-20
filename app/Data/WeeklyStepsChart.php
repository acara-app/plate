<?php

declare(strict_types=1);

namespace App\Data;

use App\Ai\Tools\ShowWeeklySteps;
use DateTimeImmutable;
use DateTimeZone;

final readonly class WeeklyStepsChart
{
    private const int MAX_SAFE_INTEGER = 9_007_199_254_740_991;

    /**
     * @return array{spec: array<string, mixed>, fallbackText: string}|null
     */
    public static function fromToolResult(string $name, mixed $result): ?array
    {
        if ($name !== ShowWeeklySteps::NAME || ! is_string($result)) {
            return null;
        }

        $summary = json_decode($result, true);
        if (! is_array($summary) || ($summary['success'] ?? null) !== true) {
            return null;
        }

        $days = self::days($summary);
        if ($days === null) {
            return null;
        }

        $noReading = (string) __('tools.weekly_steps_no_reading');
        $readings = [];
        foreach ($days as $day) {
            $readings[] = $day['date'].': '.($day['steps'] ?? $noReading);
        }

        return [
            'spec' => [
                'root' => 'weekly-steps',
                'elements' => [
                    'weekly-steps' => [
                        'type' => 'WeeklyStepsChart',
                        'props' => ['days' => $days],
                    ],
                ],
            ],
            'fallbackText' => (string) __('tools.weekly_steps_summary', ['days' => implode(', ', $readings)]),
        ];
    }

    /**
     * @param  array<mixed>  $summary
     * @return list<array{date: string, steps: int|null}>|null
     */
    private static function days(array $summary): ?array
    {
        $range = $summary['date_range'] ?? null;
        if (! is_array($range)) {
            return null;
        }

        $from = $range['from'] ?? null;
        $rows = $summary['summaries'] ?? null;
        if (! is_string($from) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $from)
            || ! is_array($rows) || ! array_is_list($rows) || $rows === [] || count($rows) > 7) {
            return null;
        }

        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $from, new DateTimeZone('UTC'));
        if (! $start || $start->format('Y-m-d') !== $from
            || $start->modify('+6 days')->format('Y-m-d') !== ($range['to'] ?? null)) {
            return null;
        }

        $stepsByDate = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ($row['type'] ?? null) !== 'stepCount') {
                return null;
            }

            $date = $row['date'] ?? null;
            $steps = $row['primary_value'] ?? null;
            if (! is_string($date) || array_key_exists($date, $stepsByDate)
                || ($steps !== null && (! is_int($steps) || $steps < 0 || $steps > self::MAX_SAFE_INTEGER))) {
                return null;
            }

            $stepsByDate[$date] = $steps;
        }

        $days = [];
        for ($offset = 0; $offset < 7; $offset++) {
            $date = $start->modify("+{$offset} days")->format('Y-m-d');
            $days[] = ['date' => $date, 'steps' => $stepsByDate[$date] ?? null];
            unset($stepsByDate[$date]);
        }

        if ($stepsByDate !== [] || ! array_any($days, static fn (array $day): bool => $day['steps'] !== null)) {
            return null;
        }

        return $days;
    }
}
