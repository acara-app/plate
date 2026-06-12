<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Data\Benchmark\CalibrationBin;
use App\Data\Benchmark\HarnessReport;
use App\Data\Benchmark\PathDelta;
use App\Data\Benchmark\PathMetrics;
use App\Models\BenchmarkMeal;
use App\Models\BenchmarkRun;
use App\Services\Benchmark\BenchmarkHarness;
use App\Services\Benchmark\RunComparator;
use App\Services\ModelPricing;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

#[Description('Run the golden-plate validation benchmark through the production analyzer — raw model and reference-enriched paths side by side.')]
#[Signature('benchmark:run {--smoke : Analyze only the smoke subset (first 12 meals by code)} {--repeats=5 : Analyses per meal per path} {--force : Skip the cost confirmation}')]
final class RunBenchmarkCommand extends Command
{
    private const int SMOKE_LIMIT = 12;

    public function __construct(
        private readonly BenchmarkHarness $harness,
        private readonly RunComparator $comparator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $repeats = max(1, (int) $this->option('repeats'));

        $query = BenchmarkMeal::query()->with('items')->orderBy('code');

        if ($this->option('smoke')) {
            $query->limit(self::SMOKE_LIMIT);
        }

        $meals = $query->get();

        if ($meals->isEmpty()) {
            $this->error('No benchmark meals collected yet. Record meals with benchmark:add-meal.');

            return self::FAILURE;
        }

        $analyses = $meals->count() * $repeats * 2;
        $estimatedCost = $this->estimateCost($analyses);

        $this->info(sprintf(
            'Benchmarking %d meals × %d repeats × 2 paths = %d analyses on %s (~$%.2f estimated).',
            $meals->count(),
            $repeats,
            $analyses,
            FoodPhotoAnalyzerAgent::version(),
            $estimatedCost,
        ));

        if (! $this->option('force') && ! confirm(label: sprintf('Run %d analyses (~$%.2f)?', $analyses, $estimatedCost), default: false)) {
            $this->info('Aborted. Nothing was run.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($analyses);
        $report = $this->harness->run($meals, $repeats, function () use ($bar): void {
            $bar->advance();
        });
        $bar->finish();
        $this->newLine(2);

        $previous = BenchmarkRun::latestComparable((bool) $this->option('smoke'));

        $run = BenchmarkRun::query()->create([
            'analyzer_version' => $report->analyzerVersion,
            'reference_lookup_enabled' => $report->referenceLookupEnabled,
            'smoke' => (bool) $this->option('smoke'),
            'repeats' => $report->repeats,
            'meal_count' => $meals->count(),
            'skipped_meals' => $report->skippedMeals,
            'report' => $report->toArray(),
        ]);

        $this->render($report);

        if ($previous !== null) {
            $this->renderComparison($report, $previous);
        }

        $this->newLine();
        $this->info(sprintf('Saved as benchmark run #%d.', $run->id));

        return self::SUCCESS;
    }

    private function renderComparison(HarnessReport $report, BenchmarkRun $previous): void
    {
        $deltas = $this->comparator->compare($report, $previous->toHarnessReport());

        if ($deltas === []) {
            return;
        }

        $this->newLine();
        $this->info(sprintf(
            'Versus run #%d (%s, %s, %d meals) — positive error deltas mean regression:',
            $previous->id,
            $previous->analyzer_version,
            $previous->created_at->toDateString(),
            $previous->meal_count,
        ));

        $this->table(
            ['Path', 'Δ Carb MAE (g)', 'Δ Carb MAPE (%)', 'Δ Energy MAPE (%)', 'Δ Item recall'],
            array_map(fn (PathDelta $delta): array => [
                $delta->path->label(),
                $this->formatDelta($delta->carbMae),
                $this->formatDelta($delta->carbMape),
                $this->formatDelta($delta->energyMape),
                $this->formatDelta($delta->itemRecall),
            ], $deltas),
        );
    }

    private function formatDelta(?float $value, int $decimals = 2): string
    {
        return $value === null ? '—' : sprintf('%+.'.$decimals.'f', $value);
    }

    private function render(HarnessReport $report): void
    {
        $this->components->twoColumnDetail('Analyzer version', $report->analyzerVersion);
        $this->components->twoColumnDetail('Reference lookup', $report->referenceLookupEnabled ? 'enabled' : 'disabled');
        $this->components->twoColumnDetail('Repeats per meal per path', (string) $report->repeats);

        if ($report->skippedMeals > 0) {
            $this->warn(sprintf('%d meals skipped because their photo could not be loaded.', $report->skippedMeals));
        }

        /** @var PathMetrics $pathMetrics */
        foreach ($report->paths as $pathMetrics) {
            $this->renderPath($pathMetrics);
        }

        $this->newLine();
        $this->line('Accuracy numbers are findings for this analyzer version — interpret against the dataset size above.');
    }

    private function renderPath(PathMetrics $pathMetrics): void
    {
        $metrics = $pathMetrics->metrics;

        $this->newLine();
        $this->info($pathMetrics->path->label());

        $this->table(['Metric', 'Value'], [
            ['Meals / runs / failed runs', sprintf('%d / %d / %d', $metrics->mealCount, $metrics->runCount, $pathMetrics->failedRuns)],
            ['Carb MAE (g)', $this->formatValue($metrics->carbs->mae)],
            ['Carb MAPE (%)', $this->formatValue($metrics->carbs->mape)],
            ['Energy MAPE (%)', $this->formatValue($metrics->calories->mape)],
            ['Protein MAPE (%)', $this->formatValue($metrics->protein->mape)],
            ['Fat MAPE (%)', $this->formatValue($metrics->fat->mape)],
            ['Macro-ratio error (pp, c/p/f)', sprintf(
                '%s / %s / %s',
                $this->formatValue($metrics->macroRatio->carbsPp),
                $this->formatValue($metrics->macroRatio->proteinPp),
                $this->formatValue($metrics->macroRatio->fatPp),
            )],
            ['Portion-bias slope (kcal error per g of meal)', $this->formatValue($metrics->portionBias->calories, 4)],
            ['Portion-bias slope (carb g error per g of meal)', $this->formatValue($metrics->portionBias->carbs, 4)],
            ['Run-to-run SD, carbs (g)', $this->formatValue($metrics->repeatability->carbs)],
            ['Item recall / precision', sprintf(
                '%s / %s (%d meals)',
                $this->formatValue($metrics->itemization->recall),
                $this->formatValue($metrics->itemization->precision),
                $metrics->itemization->mealsMeasured,
            )],
        ]);

        $this->table(
            ['Confidence', 'Runs', 'Median carb error (g)', 'Median carb error (%)'],
            array_map(fn (CalibrationBin $bin): array => [
                sprintf('%d–%d', $bin->minConfidence, $bin->maxConfidence),
                $bin->sampleCount,
                $this->formatValue($bin->medianAbsoluteCarbError),
                $this->formatValue($bin->medianAbsoluteCarbErrorPercent),
            ], $pathMetrics->metrics->calibration->items()),
        );
    }

    private function estimateCost(int $analyses): float
    {
        $pricing = ModelPricing::forModel(FoodPhotoAnalyzerAgent::pinnedModel());

        /** @var array{input: int, output: int} $budget */
        $budget = config()->array('plate.ai_usage_preflight.token_budget', ['input' => 2_000, 'output' => 1_000]);

        return $analyses * ($budget['input'] * $pricing['input'] + $budget['output'] * $pricing['output']) / 1_000_000;
    }

    private function formatValue(?float $value, int $decimals = 2): string
    {
        return $value === null ? '—' : number_format($value, $decimals);
    }
}
