<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\BuildMealEstimateReport;
use App\Data\MealEstimateReportData;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Report how often people correct AI meal estimates, by model, and which foods they rename, add or remove')]
#[Signature('meal-estimates:report
        {--days=30 : Report meals saved in the last N days}')]
final class ReportMealEstimatesCommand extends Command
{
    public function handle(BuildMealEstimateReport $buildReport): int
    {
        $to = CarbonImmutable::now();
        $report = $buildReport->handle($to->subDays(max(1, (int) $this->option('days'))), $to);

        $from = CarbonImmutable::parse($report->from)->format('Y-m-d H:i');
        $until = CarbonImmutable::parse($report->to)->format('Y-m-d H:i');
        $this->info("Meal estimate accuracy · meals saved {$from} → {$until} UTC");

        if ($report->meals < $report->minimumMeals) {
            $this->line("Not enough meals yet: fewer than {$report->minimumMeals} in this period.");

            return self::SUCCESS;
        }

        $this->line("{$report->meals} meals, {$report->corrected} corrected ({$this->percent($report->corrected, $report->meals)})");

        $this->renderModels($report);
        $this->renderFoods(
            ['AI food', 'Changed to', 'Meals'],
            array_map(fn (array $row): array => [$row['aiFood'], $row['correctedTo'], $row['meals']], $report->renamedFoods),
            "No food was renamed in {$report->minimumMeals} or more meals yet.",
        );
        $this->renderFoods(
            ['Change', 'Food', 'Meals'],
            array_map(fn (array $row): array => [$row['change'], $row['food'], $row['meals']], $report->changedFoods),
            "No food was added or removed in {$report->minimumMeals} or more meals yet.",
        );

        $this->line('Notes');

        foreach ($this->notes($report) as $note) {
            $this->line(" - {$note}");
        }

        return self::SUCCESS;
    }

    private function renderModels(MealEstimateReportData $report): void
    {
        $this->table(
            ['Model', 'Source', 'Meals', 'Corrected', 'kcal change when corrected (median)', 'Carbs change when corrected (median)'],
            array_map(fn (array $row): array => [
                $row['model'],
                $row['source'],
                $row['meals'],
                "{$row['corrected']} ({$this->percent($row['corrected'], $row['meals'])})",
                $this->kcalChange($row['medianKcalChange'], $row['medianKcalChangePercent']),
                $row['medianCarbsChange'] === null ? '—' : sprintf('%+.1f g', $row['medianCarbsChange']),
            ], $report->models),
        );
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<int|string>>  $rows
     */
    private function renderFoods(array $headers, array $rows, string $empty): void
    {
        if ($rows === []) {
            $this->line($empty);

            return;
        }

        $this->table($headers, $rows);
    }

    /**
     * @return list<string>
     */
    private function notes(MealEstimateReportData $report): array
    {
        $notes = [];

        if ($report->hiddenModelGroups > 0) {
            $notes[] = $report->hiddenModelGroups === 1
                ? "1 model group with fewer than {$report->minimumMeals} meals is hidden."
                : "{$report->hiddenModelGroups} model groups with fewer than {$report->minimumMeals} meals are hidden.";
        }

        $notes[] = 'A correction is what someone changed, not a verified measurement.';
        $notes[] = 'After a rename, the nutrition may come from the app re-estimating the new name rather than a weighed amount.';

        return $notes;
    }

    private function kcalChange(?float $change, ?float $percent): string
    {
        if ($change === null) {
            return '—';
        }

        return $percent === null ? sprintf('%+.0f', $change) : sprintf('%+.0f (%+.0f%%)', $change, $percent);
    }

    private function percent(int $count, int $of): string
    {
        return $of === 0 ? '—' : round($count / $of * 100).'%';
    }
}
